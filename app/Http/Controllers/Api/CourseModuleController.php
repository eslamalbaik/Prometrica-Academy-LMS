<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CourseModuleController extends Controller
{
    /** POST /api/dashboard/modules */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title'     => 'required|string|max:255',
            'order'     => 'integer'
        ]);

        if (!isset($validated['order'])) {
            $validated['order'] = Module::where('course_id', $validated['course_id'])->max('order') + 1;
        }

        $module = Module::create($validated);
        return response()->json(['message' => 'Module created successfully', 'module' => $module->load('lessons')], 201);
    }

    /** PUT /api/dashboard/modules/{id} — Rename a module */
    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);
        $module->update($validated);
        return response()->json(['message' => 'Module updated', 'module' => $module]);
    }

    /** DELETE /api/dashboard/modules/{id} — Delete module + cascade lessons */
    public function destroy($id)
    {
        $module = Module::findOrFail($id);
        $module->lessons()->delete(); // delete lessons first
        $module->delete();
        return response()->json(['message' => 'Module deleted']);
    }
}

