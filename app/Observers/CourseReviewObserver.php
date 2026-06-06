<?php

namespace App\Observers;

use App\Models\CourseReview;
use App\Models\User;
use App\Notifications\CourseRatedNotification;

class CourseReviewObserver
{
    /**
     * Handle the CourseReview "created" event.
     */
    public function created(CourseReview $courseReview): void
    {
        $student = $courseReview->user;
        $course = $courseReview->course;

        if ($student && $course) {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new CourseRatedNotification($student, $course, $courseReview));
            }
        }
    }
}
