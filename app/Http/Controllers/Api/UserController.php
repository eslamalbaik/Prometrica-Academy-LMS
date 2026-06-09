<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Bundle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** GET /api/dashboard/students */
    public function students()
    {
        $students = User::where('role', 'student')->latest()->get();
        return response()->json($students);
    }

    /** POST /api/dashboard/students — Admin creates a student manually */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $student = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'student', // Always force student role
        ]);

        return response()->json([
            'message' => 'Student created successfully.',
            'student' => $student,
        ], 201);
    }

    /** GET /api/dashboard/enrollments — Paginated enrollments with eager loading */
    public function enrollments()
    {
        $paginator = Enrollment::with(['user:id,name,email', 'course:id,title'])
            ->latest()
            ->paginate(15);

        // Transform items while preserving pagination meta
        $paginator->getCollection()->transform(fn($e) => [
            'id'            => $e->id,
            'student_name'  => $e->user?->name,
            'student_email' => $e->user?->email,
            'course_title'  => $e->course?->title,
            'enrolled_at'   => $e->created_at,
            'progress'      => $e->progress ?? 0,
            'device_locked' => !empty($e->device_id),
            'device_id'     => $e->device_id,
        ]);

        return response()->json($paginator);
    }

    /** GET /api/dashboard/students/{id}/detail — Progress + bundles for one student */
    public function studentDetail($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        // All course enrollments with progress
        $enrollments = Enrollment::with(['course:id,title,thumbnail'])
            ->where('user_id', $id)
            ->latest()
            ->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'course_id'    => $e->course_id,
                'course_title' => $e->course?->title,
                'thumbnail'    => $e->course?->thumbnail,
                'progress'     => $e->progress ?? 0,
                'enrolled_at'  => $e->enrolled_at ?? $e->created_at,
                'expires_at'   => $e->expires_at,
                'bundle_id'    => $e->bundle_id,
            ]);

        // Distinct bundles the student purchased
        $bundleIds = Enrollment::where('user_id', $id)
            ->whereNotNull('bundle_id')
            ->pluck('bundle_id')
            ->unique()
            ->values();

        $bundles = Bundle::whereIn('id', $bundleIds)
            ->withCount('courses')
            ->get()
            ->map(fn($b) => [
                'id'           => $b->id,
                'name'         => $b->name,
                'name_en'      => $b->name_en,
                'price'        => $b->price,
                'courses_count'=> $b->courses_count,
                'access_days'  => $b->access_days,
            ]);

        return response()->json([
            'student'     => [
                'id'         => $student->id,
                'name'       => $student->name,
                'email'      => $student->email,
                'created_at' => $student->created_at,
            ],
            'enrollments' => $enrollments,
            'bundles'     => $bundles,
        ]);
    }

    /** POST /api/dashboard/enrollments/{id}/reset-device — Admin clears the device lock */
    public function resetDeviceLock($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update(['device_id' => null]);

        return response()->json([
            'message' => 'Device lock cleared. Student can now login from any device.',
        ]);
    }

    /** POST /api/dashboard/enrollments — Admin manually enrolls, atomic duplicate prevention */
    public function adminEnroll(Request $request)
    {
        $validated = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        // firstOrCreate is atomic — safe even if admin double-clicks
        [$enrollment, $created] = [
            Enrollment::firstOrCreate(
                ['user_id' => $validated['user_id'], 'course_id' => $validated['course_id']],
                ['progress' => 0]
            ),
            false,
        ];

        $created = !$enrollment->wasRecentlyCreated ? false : true;

        if (!$enrollment->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Student is already enrolled in this course.',
            ], 409);
        }

        return response()->json([
            'message'    => 'Student enrolled successfully.',
            'enrollment' => $enrollment,
        ], 201);
    }
}

