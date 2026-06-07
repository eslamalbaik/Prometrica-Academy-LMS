<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD for tiered course packages (Basic / Silver / Gold ...).
 * Each package carries an `entitlements` JSON of feature flags.
 */
class CoursePackageController extends Controller
{
    private array $rules = [
        'name'                      => 'required|string|max:255',
        'price'                     => 'required|numeric|min:0',
        'sort'                      => 'nullable|integer|min:0',
        'entitlements'              => 'required|array',
        'entitlements.has_quizzes'     => 'required|boolean',
        'entitlements.has_files'       => 'required|boolean',
        'entitlements.has_certificate' => 'required|boolean',
    ];

    /** GET /api/dashboard/courses/{course}/packages */
    public function index(Course $course)
    {
        return response()->json(
            $course->packages()->get(['id', 'course_id', 'name', 'price', 'entitlements', 'sort'])
        );
    }

    /** POST /api/dashboard/courses/{course}/packages */
    public function store(Request $request, Course $course)
    {
        $data = $request->validate($this->rules);
        $data['course_id'] = $course->id;

        $package = CoursePackage::create($data);

        return response()->json($package, 201);
    }

    /** PUT /api/dashboard/course-packages/{package} */
    public function update(Request $request, CoursePackage $package)
    {
        $data = $request->validate($this->rules);
        $package->update($data);

        return response()->json($package);
    }

    /** DELETE /api/dashboard/course-packages/{package} */
    public function destroy(CoursePackage $package)
    {
        $package->delete();

        return response()->json(['message' => 'Package deleted']);
    }
}
