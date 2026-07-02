<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Services\DeviceVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentLessonController extends Controller
{
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $deviceService = new DeviceVerificationService();

        // ── 1. Resolve course via module relationship ─────────────────────────
        $lesson->loadMissing('module:id,course_id');
        $courseId = $lesson->module?->course_id;

        // ── 2. Verify the student is enrolled ────────────────────────────────
        if ($courseId) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'locked'  => true,
                    'message' => 'You are not enrolled in this course.',
                ], 403);
            }

            // ── 3. Device verification ────────────────────────────────────────
            $verification = $deviceService->verifyDevice($enrollment, $request);

            if ($verification['verified'] === false) {
                return response()->json(
                    $deviceService->formatErrorResponse($verification),
                    423
                );
            }

            // First time access or update needed
            if ($verification['should_update'] ?? false) {
                $enrollment->update([
                    'device_id' => $verification['fingerprint'],
                    'device_ip' => $verification['ip'],
                    'last_accessed_at' => now(),
                ]);
            } else {
                // Update last access time
                $enrollment->update(['last_accessed_at' => now()]);
            }
        }

        // ── 4. Content locking & progression control ─────────────────────────
        $previousLesson = Lesson::where('course_module_id', $lesson->course_module_id)
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousLesson) {
            $isCompleted = $user->completedLessons()
                ->where('lesson_id', $previousLesson->id)
                ->exists();

            if (!$isCompleted) {
                return response()->json([
                    'locked'  => true,
                    'message' => 'Complete the previous lesson first.',
                ], 403);
            }
        }

        $lesson->load('attachments');

        return response()->json($lesson);
    }

    public function downloadAttachment(Request $request, LessonAttachment $attachment)
    {
        $attachment->increment('download_count');

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->title);
    }
}
