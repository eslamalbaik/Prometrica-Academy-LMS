<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleCourse;
use App\Models\BundleDigitalProduct;
use App\Models\BundleQuizPermission;
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
            ->map(fn($bundle) => [
                'id'              => $bundle->id,
                'name'            => $bundle->name,
                'name_en'         => $bundle->name_en,
                'description'     => $bundle->description,
                'description_en'  => $bundle->description_en,
                'price'           => $bundle->price,
                'image'           => $bundle->image,
                'access_days'     => $bundle->access_days,
                'type'            => $bundle->type,
                'is_featured'     => $bundle->is_featured,
                'badge'           => $bundle->badge,
                'badge_en'        => $bundle->badge_en,
                'color'           => $bundle->color,
                'cta_label'       => $bundle->cta_label,
                'cta_label_en'    => $bundle->cta_label_en,
                'period'          => $bundle->period,
                'period_en'       => $bundle->period_en,
                'features'        => $bundle->features,
                'courses_count'   => $bundle->courses->count(),
                'courses'         => $bundle->courses->map(fn($c) => [
                    'id'        => $c->id,
                    'title'     => $c->title,
                    'thumbnail' => $c->thumbnail,
                ]),
            ]);

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
            'name'                         => 'required|string|max:255',
            'name_en'                      => 'nullable|string|max:255',
            'description'                  => 'nullable|string',
            'description_en'               => 'nullable|string',
            'price'                        => 'required|numeric|min:0',
            'image'                        => 'nullable|image|max:2048',
            'is_active'                    => 'boolean',
            'sort'                         => 'integer|min:0',
            'access_days'                  => 'nullable|integer|min:1',
            'type'                         => 'in:fixed,flexible,subscription',
            'max_courses'                  => 'nullable|integer|min:1',
            'auto_renewal'                 => 'boolean',
            'default_quiz_visibility'      => 'in:all,none,selected',
            'default_certificate_enabled'  => 'boolean',
            'default_products_visibility'  => 'in:all,none,selected',
            // Display fields
            'badge'                        => 'nullable|string|max:100',
            'badge_en'                     => 'nullable|string|max:100',
            'is_featured'                  => 'boolean',
            'color'                        => 'nullable|string|max:30',
            'cta_label'                    => 'nullable|string|max:100',
            'cta_label_en'                 => 'nullable|string|max:100',
            'period'                       => 'nullable|string|max:50',
            'period_en'                    => 'nullable|string|max:50',
            'features'                     => 'nullable|array',
            'course_ids'                   => 'nullable|array',
            'course_ids.*'                 => 'exists:courses,id',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('bundles', 'public');
        }

        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);

        $bundle = Bundle::create($validated);

        if (! empty($courseIds)) {
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
            'name'                         => 'sometimes|string|max:255',
            'name_en'                      => 'nullable|string|max:255',
            'description'                  => 'nullable|string',
            'description_en'               => 'nullable|string',
            'price'                        => 'sometimes|numeric|min:0',
            'image'                        => 'nullable|image|max:2048',
            'is_active'                    => 'boolean',
            'sort'                         => 'integer|min:0',
            'access_days'                  => 'nullable|integer|min:1',
            'type'                         => 'in:fixed,flexible,subscription',
            'max_courses'                  => 'nullable|integer|min:1',
            'auto_renewal'                 => 'boolean',
            'default_quiz_visibility'      => 'in:all,none,selected',
            'default_certificate_enabled'  => 'boolean',
            'default_products_visibility'  => 'in:all,none,selected',
            // Display fields
            'badge'                        => 'nullable|string|max:100',
            'badge_en'                     => 'nullable|string|max:100',
            'is_featured'                  => 'boolean',
            'color'                        => 'nullable|string|max:30',
            'cta_label'                    => 'nullable|string|max:100',
            'cta_label_en'                 => 'nullable|string|max:100',
            'period'                       => 'nullable|string|max:50',
            'period_en'                    => 'nullable|string|max:50',
            'features'                     => 'nullable|array',
            'course_ids'                   => 'nullable|array',
            'course_ids.*'                 => 'exists:courses,id',
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

    // ─── Per-course permission overrides ─────────────────────────────────────

    /**
     * PUT /api/dashboard/bundles/{bundle}/courses/{course}/permissions
     * Override quiz / certificate / products visibility for one course inside a bundle.
     */
    public function updateCoursePermissions(Request $request, Bundle $bundle, int $courseId)
    {
        $data = $request->validate([
            'quiz_visibility'     => 'sometimes|in:inherit,all,none,selected',
            'certificate_enabled' => 'sometimes|in:inherit,yes,no',
            'products_visibility' => 'sometimes|in:inherit,all,none,selected',
            'sort'                => 'sometimes|integer|min:0',
        ]);

        $bundleCourse = BundleCourse::where('bundle_id', $bundle->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        $bundleCourse->update($data);

        return response()->json([
            'message'       => 'Course permissions updated.',
            'bundle_course' => $bundleCourse,
        ]);
    }

    /**
     * PUT /api/dashboard/bundles/{bundle}/courses/{course}/quiz-permissions
     * Body: { quiz_ids: [1,2,3] }  — replaces ALL selected quiz permissions for this course.
     */
    public function syncQuizPermissions(Request $request, Bundle $bundle, int $courseId)
    {
        $data = $request->validate([
            'quiz_ids'   => 'required|array',
            'quiz_ids.*' => 'exists:quizzes,id',
        ]);

        $bundleCourse = BundleCourse::where('bundle_id', $bundle->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        // Delete existing and re-insert
        BundleQuizPermission::where('bundle_id', $bundle->id)
            ->where('bundle_course_id', $bundleCourse->id)
            ->delete();

        $rows = array_map(fn($qid) => [
            'bundle_id'       => $bundle->id,
            'bundle_course_id'=> $bundleCourse->id,
            'quiz_id'         => $qid,
            'is_visible'      => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $data['quiz_ids']);

        BundleQuizPermission::insert($rows);

        return response()->json(['message' => 'Quiz permissions updated.', 'quiz_ids' => $data['quiz_ids']]);
    }

    /**
     * PUT /api/dashboard/bundles/{bundle}/digital-products
     * Body: { product_ids: [1,2,3] }  — syncs which digital products are in the bundle.
     */
    public function syncDigitalProducts(Request $request, Bundle $bundle)
    {
        $data = $request->validate([
            'product_ids'   => 'required|array',
            'product_ids.*' => 'exists:digital_products,id',
        ]);

        // Sync the pivot (mark all as included)
        $sync = [];
        foreach ($data['product_ids'] as $pid) {
            $sync[$pid] = ['is_included' => true];
        }
        $bundle->digitalProducts()->sync($sync);

        return response()->json(['message' => 'Digital products updated.', 'product_ids' => $data['product_ids']]);
    }

    /**
     * GET /api/dashboard/bundles/{bundle}/permissions-summary
     * Returns full permission details for the admin panel.
     */
    public function permissionsSummary(Bundle $bundle)
    {
        $bundle->load([
            'courses:id,title',
            'bundleCourses.quizPermissions.quiz:id,title',
            'digitalProducts:id,title',
        ]);

        return response()->json(['bundle' => $bundle]);
    }

    // ─── Student purchase ─────────────────────────────────────────────────────

    /** POST /api/v1/bundles/{bundle}/purchase */
    public function purchase(Request $request, Bundle $bundle)
    {
        if (! $bundle->is_active) {
            return response()->json(['message' => 'This bundle is not available.'], 404);
        }

        $user      = $request->user();
        $expiresAt = $bundle->computeExpiresAt();

        DB::beginTransaction();
        try {
            foreach ($bundle->courses as $course) {
                $existing = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existing) {
                    // Direct enrollment already exists → keep it (more permissive)
                    if (is_null($existing->bundle_id)) continue;

                    // Bundle enrollment exists → upgrade if new one is better (longer)
                    $existingExpiry = $existing->expires_at;
                    $better = is_null($expiresAt)                                       // lifetime wins
                        || (! is_null($existingExpiry) && $expiresAt->gt($existingExpiry)); // later expiry wins

                    if ($better) {
                        $existing->update(['bundle_id' => $bundle->id, 'expires_at' => $expiresAt]);
                    }

                    continue;
                }

                $enrollment = Enrollment::create([
                    'user_id'     => $user->id,
                    'course_id'   => $course->id,
                    'bundle_id'   => $bundle->id,
                    'enrolled_at' => now(),
                    'expires_at'  => $expiresAt,
                    'progress'    => 0,
                ]);

                try {
                    app(StudyPlanService::class)->generateForEnrollment($enrollment);
                } catch (\Throwable $e) {
                    Log::warning('Study plan generation failed for bundle enrollment: ' . $e->getMessage());
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
