<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function enroll(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        // Check if already enrolled
        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($isEnrolled) {
            return response()->json([
                'message' => 'You are already enrolled in this course.'
            ], 400);
        }

        // Check paid vs free logic
        $price = $course->discount_price ?? $course->price;
        if (!$course->is_free && $price > 0 && !$request->input('payment_confirmed')) {
            return response()->json([
                'requires_payment' => true,
                'message' => 'This course requires payment to enroll.'
            ]);
        }

        // Enroll the user
        // Compute the access window: lifetime if access_days is null.
        $expiresAt = $course->access_days
            ? now()->addDays($course->access_days)
            : null;

        Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
            'expires_at'  => $expiresAt,
        ]);

        return response()->json([
            'message' => 'Successfully enrolled in the course.',
            'expires_at' => $expiresAt,
            'redirect_url' => '/student/dashboard'
        ], 200);
    }
}
