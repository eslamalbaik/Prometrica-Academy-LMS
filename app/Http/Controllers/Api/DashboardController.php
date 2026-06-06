<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Live counts — no cache so stats are always accurate
        $totalStudents    = User::where('role', 'student')->count();
        $activeCourses    = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::count();
        $totalRevenue        = 0; // Payment module not yet integrated
        $certificatesIssued  = Certificate::count();

        $latestStudents = DB::table('users')
            ->select('id', 'name', 'email', 'created_at')
            ->where('role', 'student')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $latestPayments = DB::table('enrollments')
            ->join('users',   'enrollments.user_id',   '=', 'users.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->select(
                'enrollments.id',
                'users.name as student',
                'courses.title as course',
                'enrollments.created_at as date',
                DB::raw('"Enrolled" as status'),
                DB::raw('"—" as method')
            )
            ->orderByDesc('enrollments.created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_students'    => $totalStudents,
                'active_courses'    => $activeCourses,
                'total_enrollments' => $totalEnrollments,
                'total_revenue'     => $totalRevenue,
                'certificates_issued' => $certificatesIssued,
            ],
            'latestStudents' => $latestStudents,
            'latestPayments' => $latestPayments,
        ]);
    }
}

