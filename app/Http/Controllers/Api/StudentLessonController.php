<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentLessonController extends Controller
{
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();

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

            // ── 3. Device-lock check ──────────────────────────────────────────
            $incomingDeviceId = $request->header('X-Device-ID');

            if ($incomingDeviceId) {
                if (empty($enrollment->device_id)) {
                    // First access from any device → register it
                    $enrollment->update(['device_id' => $incomingDeviceId]);
                } elseif ($enrollment->device_id !== $incomingDeviceId) {
                    // Different device → block access
                    return response()->json([
                        'locked'        => true,
                        'device_locked' => true,
                        'message'       => 'هذا الكورس مرتبط بجهاز آخر. تواصل مع الدعم لإلغاء القفل.',
                        'message_en'    => 'This course is locked to another device. Contact support to unlock.',
                    ], 423);
                }
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
