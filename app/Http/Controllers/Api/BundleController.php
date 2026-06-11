<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Enrollment;
use App\Services\StudyPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BundleController extends Controller
{
    // ─── Public landing ───────────────────────────────────────────────────────

    /** GET /api/landing/bundles */
    public function landing()
    {
        $bundles = Bundle::with('courses:id,title,thumbnail')
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(function ($bundle) {
                return [
                    'id'              => $bundle->id,
                    'name'            => $bundle->name,
                    'name_en'         => $bundle->name_en,
                    'description'     => $bundle->description,
                    'description_en'  => $bundle->description_en,
                    'price'           => $bundle->price,
                    'image'           => $bundle->image,
                    'access_days'     => $bundle->access_days,
                    'courses_count'   => $bundle->courses->count(),
                    'courses'         => $bundle->courses->map(fn($c) => [
                        'id'        => $c->id,
                        'title'     => $c->title,
                        'thumbnail' => $c->thumbnail,
                    ]),
                ];
            });

        return response()->json($bundles);
    }

    // ─── Admin CRUD ───────────────────────────────────────────────────────────

    /** GET /api/dashboard/bundles */
    public function index()
    {
        return response()->json(
            Bundle::with('courses:id,title,thumbnail')
                ->withCount('enrollments')
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
        );
    }

    /** POST /api/dashboard/bundles */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'description_en'  => 'nullable|string',
            'price'           => 'required|numeric|min:0',
            'image'           => 'nullable|image|max:2048',
            'is_active'       => 'boolean',
            'sort'            => 'integer|min:0',
            'access_days'     => 'nullable|integer|min:1',
            'course_ids'      => 'nullable|array',
            'course_ids.*'    => 'exists:courses,id',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('bundles', 'public');
        }

        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);

        $bundle = Bundle::create($validated);
        if (!empty($courseIds)) {
            $bundle->courses()->sync($courseIds);
        }

        return response()->json([
            'message' => 'Bundle created successfully',
            'bundle'  => $bundle->load('courses:id,title,thumbnail'),
        ], 201);
    }

    /** PUT /api/dashboard/bundles/{bundle} */
    public function update(Request $request, Bundle $bundle)
    {
        $validated = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'description_en'  => 'nullable|string',
            'price'           => 'sometimes|numeric|min:0',
            'image'           => 'nullable|image|max:2048',
            'is_active'       => 'boolean',
            'sort'            => 'integer|min:0',
            'access_days'     => 'nullable|integer|min:1',
            'course_ids'      => 'nullable|array',
            'course_ids.*'    => 'exists:courses,id',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('bundles', 'public');
        }

        $courseIds = $validated['course_ids'] ?? null;
        unset($validated['course_ids']);

        $bundle->update($validated);
        if ($courseIds !== null) {
            $bundle->courses()->sync($courseIds);
        }

        return response()->json([
            'message' => 'Bundle updated successfully',
            'bundle'  => $bundle->load('courses:id,title,thumbnail'),
        ]);
    }

    /** DELETE /api/dashboard/bundles/{bundle} */
    public function destroy(Bundle $bundle)
    {
        $bundle->delete();
        return response()->json(['message' => 'Bundle deleted successfully']);
    }

    // ─── Student purchase ─────────────────────────────────────────────────────

    /** POST /api/bundles/{bundle}/purchase */
    public function purchase(Request $request, Bundle $bundle)
    {
        if (!$bundle->is_active) {
            return response()->json(['message' => 'This bundle is not available.'], 404);
        }

        $user      = $request->user();
        $expiresAt = $bundle->access_days
            ? now()->addDays($bundle->access_days)
            : null;

        DB::beginTransaction();
        try {
            foreach ($bundle->courses as $course) {
                // Skip if already enrolled
                $exists = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->exists();

                if (!$exists) {
                    $enrollment = Enrollment::create([
                        'user_id'    => $user->id,
                        'course_id'  => $course->id,
                        'bundle_id'  => $bundle->id,
                        'enrolled_at'=> now(),
                        'expires_at' => $expiresAt,
                        'progress'   => 0,
                    ]);

                    // FTR-005: Generate study plan per enrollment
                    try {
                        app(StudyPlanService::class)->generateForEnrollment($enrollment);
                    } catch (\Throwable $e) {
                        Log::warning('Study plan generation failed for bundle enrollment: ' . $e->getMessage());
                    }
                }
            }
            DB::commit();

            return response()->json([
                'message'       => 'Bundle purchased successfully.',
                'courses_count' => $bundle->courses->count(),
                'expires_at'    => $expiresAt,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Purchase failed.', 'error' => $e->getMessage()], 500);
        }
    }
}
