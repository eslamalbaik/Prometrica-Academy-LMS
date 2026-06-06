<?php

namespace App\Observers;

use App\Models\LessonComment;
use App\Models\User;
use App\Notifications\CourseCommentedNotification;

class LessonCommentObserver
{
    /**
     * Handle the LessonComment "created" event.
     */
    public function created(LessonComment $lessonComment): void
    {
        $lessonComment->loadMissing('lesson.module.course');
        $student = $lessonComment->user;
        $lesson = $lessonComment->lesson;
        $course = $lesson?->module?->course;

        if ($student && $lesson && $course) {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new CourseCommentedNotification($student, $course, $lesson, $lessonComment));
            }
        }
    }
}
