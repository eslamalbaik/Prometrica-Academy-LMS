<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /** POST /api/dashboard/lessons */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id'        => 'required|exists:course_modules,id',
            'title'            => 'required|string|max:255',
            'video_url'        => 'required|url',
            'content'          => 'nullable|string',
            'order'            => 'integer',
            // Duration is measured client-side from the video file's metadata
            // (Bunny Storage Zone does not expose video length via API).
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        if (!isset($validated['order'])) {
            $validated['order'] = Lesson::where('course_module_id', $validated['module_id'])->max('order') + 1;
        }

        $validated['course_module_id'] = $validated['module_id'];
        unset($validated['module_id']);

        $lesson = Lesson::create($validated);

        return response()->json(['message' => 'Lesson created successfully', 'lesson' => $lesson->fresh()], 201);
    }

    /** PUT /api/dashboard/lessons/{id} */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'video_url'        => 'sometimes|url',
            'content'          => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        // Don't wipe an existing duration when the client probe failed (null).
        if (array_key_exists('duration_seconds', $validated) && $validated['duration_seconds'] === null) {
            unset($validated['duration_seconds']);
        }

        $lesson->update($validated);

        return response()->json(['message' => 'Lesson updated', 'lesson' => $lesson->fresh()]);
    }

    /** DELETE /api/dashboard/lessons/{id} */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }
}
