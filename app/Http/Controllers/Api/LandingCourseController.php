<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Course;

class LandingCourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::where('is_published', true)
            ->where('bundle_only', false)
            ->with([
                'modules' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.lessons' => function ($query) {
                    $query->orderBy('order');
                },
                'reviews' => function ($query) {
                    $query->with('user:id,name')->where('is_approved', true);
                }
            ])->get();

        // Attach is_enrolled flag for authenticated users
        $user = auth('sanctum')->user();
        if ($user) {
            $enrolledIds = \App\Models\Enrollment::where('user_id', $user->id)
                ->pluck('course_id')
                ->flip()
                ->all();

            $courses = $courses->map(function ($course) use ($user, $enrolledIds) {
                $data = $course->toArray();
                $data['is_enrolled'] = $user->role === 'admin'
                    || $user->id === $course->instructor_id
                    || isset($enrolledIds[$course->id]);
                return $data;
            });
        }

        return response()->json($courses);
    }
    
    public function show($id)
    {
        $course = Course::where('id', $id)
            ->where('is_published', true)
            ->with([
                'modules' => function ($query) {
                    $query->orderBy('order');
                },
                'modules.lessons' => function ($query) {
                    $query->orderBy('order');
                },
                'reviews' => function ($query) {
                    $query->with('user:id,name')->where('is_approved', true);
                }
            ])->firstOrFail();

        $user = auth('sanctum')->user();
        $isEnrolled = false;
        if ($user) {
            $isEnrolled = $user->role === 'admin' || $user->id === $course->instructor_id || \App\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();
        }

        $courseData = $course->toArray();
        $courseData['is_enrolled'] = $isEnrolled;

        return response()->json($courseData);
    }
}
