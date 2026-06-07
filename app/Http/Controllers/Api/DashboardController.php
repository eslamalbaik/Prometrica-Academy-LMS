<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return [
                'total_students'      => User::where('role', 'student')->count(),
                'active_courses'      => Course::where('is_published', true)->count(),
                'total_enrollments'   => Enrollment::count(),
                'total_revenue'       => 0,
                'certificates_issued' => Certificate::count(),
            ];
        });

        $latestStudents = Cache::remember('dashboard_latest_students', 120, function () {
            return DB::table('users')
                ->select('id', 'name', 'email', 'created_at')
                ->where('role', 'student')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        });

        $latestPayments = Cache::remember('dashboard_latest_payments', 120, function () {
            return DB::table('enrollments')
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
        });

        return response()->json([
            'stats'          => $stats,
            'latestStudents' => $latestStudents,
            'latestPayments' => $latestPayments,
        ]);
    }
}

