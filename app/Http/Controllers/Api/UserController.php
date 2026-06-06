<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
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
        ]);

        return response()->json($paginator);
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

