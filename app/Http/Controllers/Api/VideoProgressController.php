<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-side anti-cheat video progress (Heartbeat).
 *
 * The client sends a ping every ~15s WHILE the video is actually playing. The
 * server NEVER trusts current_time for completion: it accumulates watched_seconds
 * by a fixed step (capped per request) so the only way to reach the completion
 * threshold is to genuinely let the video play. Seeking to the end produces a
 * single ping = a single step, which can never satisfy the threshold.
 */
class VideoProgressController extends Controller
{
    /** Seconds credited per heartbeat (matches the client interval + tolerance). */
    private const STEP = 20;

    /** Fraction of the video that must be watched to auto-complete. */
    private const COMPLETE_AT = 0.90;

    /** POST /api/v1/progress/ping  { lesson_id, current_time, duration } */
    public function ping(Request $request)
    {
        $data = $request->validate([
            'lesson_id'    => 'required|integer|exists:lessons,id',
            'current_time' => 'required|numeric|min:0',
            'duration'     => 'nullable|numeric|min:0',
        ]);

        $user   = $request->user();
        $lesson = Lesson::findOrFail($data['lesson_id']);

        // Ensure a pivot row exists.
        $pivot = DB::table('lesson_user')
            ->where('lesson_id', $lesson->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            DB::table('lesson_user')->insert([
                'lesson_id'       => $lesson->id,
                'user_id'         => $user->id,
                'is_completed'    => false,
                'watched_seconds' => 0,
                'last_position'   => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $pivot = DB::table('lesson_user')
                ->where('lesson_id', $lesson->id)
                ->where('user_id', $user->id)
                ->first();
        }

        $duration = (float) ($data['duration'] ?? 0);

        // Auto-populate the lesson's duration if it's currently null or 0.
        if (($lesson->duration_seconds === null || $lesson->duration_seconds === 0) && $duration > 0) {
            $lesson->update(['duration_seconds' => (int) ceil($duration)]);
        }

        // Credit a fixed step, capped so watched_seconds can never exceed duration.
        $watched = (int) $pivot->watched_seconds + self::STEP;
        if ($duration > 0) {
            $watched = min($watched, (int) ceil($duration));
        }

        // Auto-complete only when genuinely watched past the threshold.
        $wasCompleted = (bool) $pivot->is_completed;
        $isCompleted  = $wasCompleted;
        $completedAt  = $pivot->completed_at ?? null;
        $justCompleted = false;

        if (! $isCompleted && $duration > 0 && $watched >= $duration * self::COMPLETE_AT) {
            $isCompleted   = true;
            $completedAt   = now();
            $justCompleted = true;
        }

        DB::table('lesson_user')
            ->where('id', $pivot->id)
            ->update([
                'watched_seconds' => $watched,
                'last_position'   => (int) $data['current_time'],
                'is_completed'    => $isCompleted,
                'completed_at'    => $completedAt,
                'updated_at'      => now(),
            ]);

        // ── Auto-sync study plan task (only on the transition to completed) ──
        if ($justCompleted) {
            $courseId = $lesson->with('module.course')->findOrFail($lesson->id)->module->course_id;

            // Sync enrollment progress
            app(CourseProgressService::class)->syncEnrollment($user, $courseId);

            // Mark corresponding study plan task as done
            $studyPlan = StudyPlan::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->first();

            if ($studyPlan) {
                StudyPlanTask::where('study_plan_id', $studyPlan->id)
                    ->where('lesson_id', $lesson->id)
                    ->whereNull('completed_at')
                    ->update(['completed_at' => now()]);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'watched_seconds' => $watched,
            'is_completed'    => $isCompleted,
            'threshold'       => $duration > 0 ? (int) ceil($duration * self::COMPLETE_AT) : null,
        ]);
    }
}
