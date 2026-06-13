<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager-load exactly what the `total_duration`, `lessons_count` and
        // `average_rating` appended attributes need, so the list renders with a
        // few queries instead of ~3 per course (N+1).
        $courses = \App\Models\Course::query()
            ->with([
                'instructor:id,name',
                'modules:id,course_id,title,order',
                'modules.lessons:id,course_module_id',
                'reviews:id,course_id,rating,is_approved',
            ])
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($courses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:courses',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'category' => 'nullable|string',
            'difficulty' => 'nullable|string',
            'language' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'is_free' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'access_days' => 'nullable|integer|min:1',
            'include_in_subscription' => 'nullable|boolean',
            'status' => 'nullable|string'
        ]);

        if (isset($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'draft';
        }
        
        $validated['is_published'] = ($validated['status'] === 'Published');

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $validated['instructor_id'] = $user->id;

        // Auto-generate slug from title if not provided
        if (empty($validated['slug'])) {
            $base = \Illuminate\Support\Str::slug($validated['title']);
            $slug = $base;
            $i    = 1;
            while (\App\Models\Course::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = $path;
        }

        $course = \App\Models\Course::create($validated);

        return response()->json(['message' => 'Course created successfully', 'course' => $course], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = \App\Models\Course::query();
        
        if ($request->has('include') && str_contains($request->include, 'modules.lessons')) {
            $query->with(['modules.lessons']);
        }

        $course = $query->findOrFail($id);
        
        return response()->json(['course' => $course]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $course = \App\Models\Course::findOrFail($id);

        if ($request->user()->id !== $course->instructor_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'short_description'=> 'sometimes|nullable|string',
            'description'      => 'sometimes|nullable|string',
            'category'         => 'sometimes|nullable|string',
            'difficulty'       => 'sometimes|nullable|string',
            'language'         => 'sometimes|nullable|string',
            'price'            => 'sometimes|nullable|numeric|min:0',
            'discount_price'   => 'sometimes|nullable|numeric|min:0',
            'access_days'      => 'sometimes|nullable|integer|min:1',
            'is_free'          => 'sometimes|boolean',
            'status'           => 'sometimes|string',
            'is_published'     => 'sometimes|boolean',
            'thumbnail'        => 'sometimes|nullable|image|max:2048',
        ]);

        if (isset($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = 0;
            $validated['discount_price'] = null;
        }

        // Keep is_published in sync with status
        if (isset($validated['status']) && !isset($validated['is_published'])) {
            $validated['is_published'] = ($validated['status'] === 'Published');
        }

        // Handle thumbnail upload on edit
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = $path;
        }

        $course->update($validated);

        return response()->json(['message' => 'Course updated successfully', 'course' => $course]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = \App\Models\Course::findOrFail($id);
        
        // Ensure only instructor (or admin) can delete
        if (request()->user()->id !== $course->instructor_id && request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();
        return response()->json(['message' => 'Course deleted successfully']);
    }
}
