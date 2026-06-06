<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /** POST /api/dashboard/lessons */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:course_modules,id',
            'title'     => 'required|string|max:255',
            'video_url' => 'required|url',
            'content'   => 'nullable|string',
            'order'     => 'integer'
        ]);

        if (!isset($validated['order'])) {
            $validated['order'] = Lesson::where('course_module_id', $validated['module_id'])->max('order') + 1;
        }

        $validated['course_module_id'] = $validated['module_id'];
        unset($validated['module_id']);

        $lesson = Lesson::create($validated);
        return response()->json(['message' => 'Lesson created successfully', 'lesson' => $lesson], 201);
    }

    /** PUT /api/dashboard/lessons/{id} */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $validated = $request->validate([
            'title'     => 'sometimes|string|max:255',
            'video_url' => 'sometimes|url',
            'content'   => 'nullable|string',
        ]);
        $lesson->update($validated);
        return response()->json(['message' => 'Lesson updated', 'lesson' => $lesson]);
    }

    /** DELETE /api/dashboard/lessons/{id} */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }
}

