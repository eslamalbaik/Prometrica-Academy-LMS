<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Course;

class LandingCourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_published', true)
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
