<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Course;

class CourseCompletedNotification extends Notification
{
    use Queueable;

    protected $student;
    protected $course;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $student, Course $course)
    {
        $this->student = $student;
        $this->course = $course;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_completed',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'message' => "{$this->student->name} completed the course '{$this->course->title}'.",
        ];
    }
}
