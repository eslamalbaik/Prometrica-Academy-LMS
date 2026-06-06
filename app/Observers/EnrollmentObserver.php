<?php

namespace App\Observers;

use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\CourseCompletedNotification;

class EnrollmentObserver
{
    /**
     * Handle the Enrollment "created" event.
     */
    public function created(Enrollment $enrollment): void
    {
        \App\Models\SystemStatistic::first()->increment('total_enrollments');
    }

    /**
     * Handle the Enrollment "updated" event.
     */
    public function updated(Enrollment $enrollment): void
    {
        if ($enrollment->isDirty('progress') && $enrollment->progress == 100 && $enrollment->getOriginal('progress') < 100) {
            $student = $enrollment->user;
            $course = $enrollment->course;

            if ($student && $course) {
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new CourseCompletedNotification($student, $course));
                }
            }
        }
    }
}
