<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Spatie\Browsershot\Browsershot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Implementing exponential backoff.
     */
    public array $backoff = [10, 60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $userId,
        protected int $courseId,
        protected int $tenantId,
        protected ?string $traceId = null
    ) {
        $this->traceId = $traceId ?? (string) \Illuminate\Support\Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Tenant Isolation: bootstrap Laravel Context
        Context::add('tenant_id', $this->tenantId);
        Context::add('trace_id', $this->traceId);

        // 2. Observability: Structured logging for lifecycle start
        Log::info('Certificate Generation Started', [
            'trace_id'     => $this->traceId,
            'tenant'       => $this->tenantId,
            'user_id'      => $this->userId,
            'course_id'    => $this->courseId,
            'memory_usage' => memory_get_usage(),
        ]);

        $startTime = microtime(true);

        // 3. Idempotency: Redis atomic lock based on userId and courseId
        $lockKey = "generate-cert-{$this->userId}-{$this->courseId}";
        $lock = Cache::lock($lockKey, 300); // 5 minute lock expiry to allow rendering

        if (!$lock->get()) {
            Log::warning('Certificate Generation Skipped: Duplicate process lock active.', [
                'trace_id'  => $this->traceId,
                'user_id'   => $this->userId,
                'course_id' => $this->courseId,
            ]);
            return;
        }

        try {
            // 4. Two-Phase Commit approach wrapped in a Database Transaction
            DB::transaction(function () {
                $user = User::findOrFail($this->userId);
                $course = Course::findOrFail($this->courseId);

                // Create the certificate record (triggers boot logic for ULID and Signature)
                // If it already exists, fetch it
                $certificate = Certificate::firstOrCreate([
                    'user_id'   => $this->userId,
                    'course_id' => $this->courseId,
                ]);

                // Render certificate view using Spatie Browsershot
                $landingUrl = rtrim(env('LANDING_URL', 'http://localhost:8080'), '/');
                
                // Formulate verify URL containing ULID, Signature, User ID, Course ID
                $verifyUrl = "{$landingUrl}/verify/{$certificate->ulid}" .
                             "?signature=" . urlencode($certificate->signature) .
                             "&userId={$this->userId}" .
                             "&courseId={$this->courseId}";

                $html = view('certificates.certificate', [
                    'student'    => $user->name,
                    'course'     => $course->title,
                    'category'   => $course->category ?? '',
                    'issued_at'  => $certificate->issued_at->format('F j, Y'),
                    'uuid'       => strtoupper($certificate->ulid), // Keep template variable compatible
                    'verify_url' => $verifyUrl,
                ])->render();

                // Prevent EPERM errors on Windows by ensuring a writable temporary directory is used
                $tempPath = storage_path('tmp');
                if (!file_exists($tempPath)) {
                    mkdir($tempPath, 0777, true);
                }
                putenv("TMPDIR={$tempPath}");
                putenv("TEMP={$tempPath}");
                putenv("TMP={$tempPath}");

                // Chrome creates its own profile dir via os.tmpdir(); on Windows this
                // resolves to C:\Windows\temp which is not writable → EPERM. Pointing
                // --user-data-dir at our writable storage path fixes it reliably.
                $chromeProfile = $tempPath . DIRECTORY_SEPARATOR . 'chrome-profile';
                if (!file_exists($chromeProfile)) {
                    mkdir($chromeProfile, 0777, true);
                }

                // Generate PDF using optimized Browsershot config
                Log::info('Browsershot PDF rendering starting', ['trace_id' => $this->traceId]);

                $pdfContent = Browsershot::html($html)
                    ->setCustomTempPath($tempPath)
                    ->timeout(15) // Enforce strict 15s max rendering time
                    ->noSandbox() // Enforce `--no-sandbox`
                    // Browsershot prefixes each flag with "--" automatically, so pass
                    // the bare flag name (no leading dashes) to avoid "----flag".
                    ->addChromiumArguments([
                        'disable-dev-shm-usage', // Enforce critical scaling flags
                        'disable-gpu',
                        'disable-software-rasterizer',
                        'user-data-dir' => $chromeProfile,
                    ])
                    ->showBackground()
                    ->paperSize(1191, 1685, 'px') // A4 Portrait — matches template image
                    ->pdf();

                Log::info('Browsershot PDF rendering succeeded', ['trace_id' => $this->traceId]);

                // Upload to S3/Private storage first (Two-Phase Commit Phase 1)
                $disk = env('FILESYSTEM_DISK', 'local');
                $storagePath = "certificates/{$certificate->ulid}.pdf";

                $uploaded = Storage::disk($disk)->put($storagePath, $pdfContent);

                if (!$uploaded) {
                    throw new \RuntimeException("S3/Private storage upload failed. Aborting database transaction.");
                }

                // Verify file upload success
                if (!Storage::disk($disk)->exists($storagePath)) {
                    throw new \RuntimeException("S3/Private storage verification failed. File does not exist after writing.");
                }

                Log::info('Certificate uploaded and verified on storage disk.', [
                    'trace_id' => $this->traceId,
                    'path'     => $storagePath,
                    'disk'     => $disk,
                ]);

                // Database Transaction Commits automatically at the end of DB::transaction callback
            });

            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info('Certificate Generation Succeeded', [
                'trace_id'     => $this->traceId,
                'user_id'      => $this->userId,
                'course_id'    => $this->courseId,
                'elapsed_sec'  => $elapsed,
                'memory_usage' => memory_get_usage(),
            ]);

        } finally {
            // Always release the atomic lock
            $lock->release();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Certificate Generation Failed: Routing to DLQ', [
            'trace_id'      => $this->traceId,
            'tenant'        => $this->tenantId,
            'user_id'       => $this->userId,
            'course_id'     => $this->courseId,
            'error_message' => $exception->getMessage(),
            'stack_trace'   => $exception->getTraceAsString(),
            'dlq_status'    => 'Manual SRE Intervention Required',
        ]);
        
        // In a real SRE context, this could trigger custom Slack alerts,
        // send notifications to Sentry/Telescope, or push to a physical DLQ queue.
    }
}
