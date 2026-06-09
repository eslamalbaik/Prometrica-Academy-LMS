<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Storage;

class StudentLessonController extends Controller
{
    public function show(Request $request, Lesson $lesson)
    {
        // Content Locking & Progression Control
        $previousLesson = Lesson::where('module_id', $lesson->module_id)
                                ->where('order', '<', $lesson->order)
                                ->orderBy('order', 'desc')
                                ->first();

        if ($previousLesson) {
            $isCompleted = $request->user()->completedLessons()->where('lesson_id', $previousLesson->id)->exists();
            
            if (!$isCompleted) {
                return response()->json([
                    'locked' => true,
                    'message' => 'Complete the previous lesson first.'
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
