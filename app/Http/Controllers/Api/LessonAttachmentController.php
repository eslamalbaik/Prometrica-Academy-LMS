<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentController extends Controller
{
    /** GET /api/dashboard/lessons/{id}/attachments */
    public function index($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        return response()->json($lesson->attachments);
    }

    /** POST /api/dashboard/lessons/{id}/attachments */
    public function store(Request $request, $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);

        $request->validate([
            'file'  => 'required|file|mimes:pdf|max:20480',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $path = $file->store("lesson_attachments/{$lessonId}", 'public');

        $attachment = LessonAttachment::create([
            'lesson_id' => $lesson->id,
            'title'     => $request->input('title') ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);

        return response()->json($attachment, 201);
    }

    /** DELETE /api/dashboard/lesson-attachments/{id} */
    public function destroy($id)
    {
        $attachment = LessonAttachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return response()->json(['message' => 'Attachment deleted']);
    }
}
