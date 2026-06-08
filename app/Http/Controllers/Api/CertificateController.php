<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseProgressService;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(private CourseProgressService $progressService) {}

    /** GET /student/certificates */
    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('course:id,title,thumbnail,category')
            ->orderByDesc('issued_at')
            ->get();

        return response()->json($certificates);
    }

    /** POST /student/courses/{id}/certificate */
    public function issue(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->enrolledCourses()->where('course_id', $id)->exists()) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $progress = $this->progressService->calculate($user, $id);

        if ($progress['progress_percentage'] < 100) {
            return response()->json([
                'message' => 'Course not completed yet.',
                'progress_percentage' => $progress['progress_percentage'],
            ], 422);
        }

        // Check if certificate already exists
        $certificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $id)
            ->first();

        // Determine tenant_id (default to 1 or header value if multi-tenant)
        $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : ($request->header('X-Tenant-ID') ?: 1);

        if ($certificate) {
            $disk = config('filesystems.default', 'local');
            $path = "certificates/{$certificate->ulid}.pdf";
            if (!Storage::disk($disk)->exists($path)) {
                GenerateCertificateJob::dispatch($user->id, $id, $tenantId);
                return response()->json([
                    'message' => 'Certificate file is missing. Regeneration started. Please check back in a few seconds.',
                    'status'  => 'pending'
                ], 202);
            }

            return response()->json([
                'message'     => 'Certificate already issued.',
                'certificate' => $certificate,
            ], 200);
        }

        // Dispatch asynchronous generation job
        GenerateCertificateJob::dispatch($user->id, $id, $tenantId);

        return response()->json([
            'message' => 'Certificate generation started. Please check back in a few seconds.',
            'status'  => 'pending'
        ], 202);
    }

    /** GET /api/v1/certificates/{certificate}/download */
    public function download(Request $request, Certificate $certificate): JsonResponse
    {
        // 3_authorization_instead_of_404: Authorize via Gate/Policy
        \Illuminate\Support\Facades\Gate::authorize('download', $certificate);

        // Return a temporary signed URL valid for 5 minutes
        $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'certificate.signed_download',
            now()->addMinutes(5),
            ['certificate' => $certificate->ulid]
        );

        return response()->json([
            'download_url' => $downloadUrl,
            'expires_in'   => 300,
        ]);
    }

    /** GET /api/v1/certificates/{certificate}/signed-download */
    public function signedDownload(Request $request, Certificate $certificate)
    {
        // Ensure course title is loaded
        $certificate->loadMissing('course:id,title');

        $disk = config('filesystems.default', 'local');
        $path = "certificates/{$certificate->ulid}.pdf";

        if (!Storage::disk($disk)->exists($path)) {
            // Self-healing fallback: Generate the certificate PDF on-the-fly synchronously
            try {
                $user = User::findOrFail($certificate->user_id);
                $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : 1;
                
                $job = new GenerateCertificateJob($certificate->user_id, $certificate->course_id, $tenantId);
                $job->handle();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('On-the-fly certificate generation failed', [
                    'ulid' => $certificate->ulid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 2_graceful_async_file_handling: Check if the file exists after attempt
        if (!Storage::disk($disk)->exists($path)) {
            return response()->json([
                'error'  => 'Certificate is currently being generated. Please try again in a few seconds.',
                'status' => 'pending'
            ], 409);
        }

        $filename = 'certificate-' . strtolower(str_replace(' ', '-', $certificate->course->title)) . '.pdf';

        return Storage::disk($disk)->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** GET /certificates/{ulid}/verify  (public — JSON) */
    public function verify(Request $request, string $ulid): JsonResponse
    {
        $signature = $request->query('signature');
        $userId = $request->query('userId');
        $courseId = $request->query('courseId');

        if (!$signature || !$userId || !$courseId) {
            return response()->json(['valid' => false, 'message' => 'Missing verification parameters.'], 400);
        }

        // 1. Parse signature (version:hash)
        $parts = explode(':', $signature, 2);
        if (count($parts) !== 2) {
            return response()->json(['valid' => false, 'message' => 'Malformed signature.'], 400);
        }

        $version = $parts[0];

        // 2. CRYPTOGRAPHICALLY VALIDATE SIGNATURE BEFORE QUERYING DATABASE
        try {
            $expectedSignature = Certificate::generateSignature($version, $ulid, (int)$userId, (int)$courseId);
            if (!hash_equals($expectedSignature, $signature)) {
                return response()->json(['valid' => false, 'message' => 'Signature verification failed.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Verification error.'], 400);
        }

        // 3. NOW query the database safely
        $certificate = Certificate::where('ulid', $ulid)
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->with(['user:id,name', 'course:id,title,thumbnail,category'])
            ->first();

        if (! $certificate) {
            return response()->json(['valid' => false, 'message' => 'Certificate not found in database records.'], 404);
        }

        return response()->json([
            'valid'      => true,
            'student'    => $certificate->user->name,
            'course'     => $certificate->course->title,
            'category'   => $certificate->course->category,
            'issued_at'  => $certificate->issued_at->toDateString(),
            'ulid'       => $certificate->ulid,
        ]);
    }

    /** POST /dashboard/certificates/issue  (admin — manually issue) */
    public function adminIssue(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $userId = $request->input('user_id');
        $courseId = $request->input('course_id');

        // Check if certificate already exists
        $certificate = Certificate::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        $user = User::findOrFail($userId);
        $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : 1;

        if ($certificate) {
            $disk = config('filesystems.default', 'local');
            $path = "certificates/{$certificate->ulid}.pdf";
            if (!Storage::disk($disk)->exists($path)) {
                GenerateCertificateJob::dispatch($userId, $courseId, $tenantId);
                return response()->json([
                    'message' => 'Certificate file is missing. Regeneration started.',
                    'status'  => 'pending'
                ], 202);
            }

            return response()->json([
                'message'     => 'Certificate already exists.',
                'certificate' => $certificate,
            ], 200);
        }

        // Dispatch asynchronous generation job
        GenerateCertificateJob::dispatch($userId, $courseId, $tenantId);

        return response()->json([
            'message' => 'Certificate generation started asynchronously by admin.',
            'status'  => 'pending'
        ], 202);
    }

    /** POST /student/certificates/{ulid}/regenerate  (student — own certificates only) */
    public function studentRegenerate(Request $request, string $ulid): JsonResponse
    {
        $certificate = Certificate::where('ulid', $ulid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $disk = config('filesystems.default', 'local');
        $path = "certificates/{$certificate->ulid}.pdf";

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $user = $request->user();
        $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : 1;

        GenerateCertificateJob::dispatch($certificate->user_id, $certificate->course_id, $tenantId);

        return response()->json([
            'message' => 'Certificate regeneration started. Please download again in a few seconds.',
            'status'  => 'pending',
        ], 202);
    }

    /** POST /dashboard/certificates/{ulid}/regenerate  (admin) */
    public function adminRegenerate(Request $request, string $ulid): JsonResponse
    {
        $certificate = Certificate::where('ulid', $ulid)->firstOrFail();

        $disk = config('filesystems.default', 'local');
        $path = "certificates/{$certificate->ulid}.pdf";

        // Delete the old stored PDF so the next download regenerates it fresh
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $user = User::findOrFail($certificate->user_id);
        $tenantId = method_exists($user, 'tenantId') ? $user->tenantId() : 1;

        GenerateCertificateJob::dispatch($certificate->user_id, $certificate->course_id, $tenantId);

        return response()->json([
            'message' => 'Certificate regeneration started. Please download again in a few seconds.',
            'status'  => 'pending',
        ], 202);
    }

    /** GET /dashboard/certificates  (admin) */
    public function adminIndex(Request $request): JsonResponse
    {
        $certificates = Certificate::with(['user:id,name,email', 'course:id,title,category'])
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json($certificates);
    }

    /** GET /dashboard/certificates/stats  (admin) */
    public function adminStats(): JsonResponse
    {
        $stats = Certificate::selectRaw('course_id, count(*) as issued_count')
            ->with('course:id,title,category,thumbnail')
            ->groupBy('course_id')
            ->orderByDesc('issued_count')
            ->get();

        return response()->json($stats);
    }
}
