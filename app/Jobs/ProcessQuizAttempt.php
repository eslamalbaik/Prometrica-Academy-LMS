<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class ProcessQuizAttempt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $tenantId;
    public string $uuid;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tenantId, string $uuid)
    {
        $this->tenantId = $tenantId;
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Manually bootstrap the tenant context in the background worker
        Context::add('tenant_id', $this->tenantId);

        // 2. Stateful Idempotency Check with Redis (Expires in 24 hours)
        $lockKey = 'idempotent:' . $this->uuid;
        $status = Redis::get($lockKey);

        if ($status === 'processing' || $status === 'completed') {
            Log::info('Duplicate quiz attempt skipped via Redis state check', [
                'uuid' => $this->uuid,
                'status' => $status
            ]);
            return;
        }

        // Lock the request as processing using atomic SET NX EX
        if ($status === null) {
            $isNew = Redis::set($lockKey, 'processing', 'EX', 86400, 'NX');
            if (!$isNew) {
                // Handle concurrent race window
                $status = Redis::get($lockKey);
                if ($status === 'processing' || $status === 'completed') {
                    Log::info('Duplicate quiz attempt skipped via Redis race check', [
                        'uuid' => $this->uuid,
                        'status' => $status
                    ]);
                    return;
                }
            }
        }

        // Set state to processing (whether it was fresh or failed previously)
        Redis::set($lockKey, 'processing', 'EX', 86400);

        try {
            // Simulate core processing logic
            if (empty($this->uuid)) {
                throw new InvalidArgumentException("Quiz attempt UUID cannot be empty (Poison Pill).");
            }

            // Real business logic would execute here

            // Mark attempt execution as completed
            Redis::set($lockKey, 'completed', 'EX', 86400);

        } catch (QueryException $e) {
            // Transient failure (e.g. database deadlock) - Mark failed so it can be retried by the queue
            Redis::set($lockKey, 'failed', 'EX', 86400);
            Log::warning('Quiz Attempt DB Query failed. Lock marked as failed for retry.', [
                'uuid' => $this->uuid,
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (InvalidArgumentException $e) {
            // Poison Pill - fail immediately to avoid endless useless retries
            Redis::set($lockKey, 'failed', 'EX', 86400);
            Log::error('Quiz Attempt Failed', [
                'uuid' => $this->uuid,
                'type' => 'poison_pill',
                'tenant' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        } catch (\Throwable $e) {
            // Generic failure - Mark failed and fail the job
            Redis::set($lockKey, 'failed', 'EX', 86400);
            $this->fail($e);
        }
    }
}
