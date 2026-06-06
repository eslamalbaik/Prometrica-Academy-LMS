<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseReview;

class CourseRatedNotification extends Notification
{
    use Queueable;

    protected $student;
    protected $course;
    protected $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $student, Course $course, CourseReview $review)
    {
        $this->student = $student;
        $this->course = $course;
        $this->review = $review;
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
            'type' => 'course_rated',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'rating' => $this->review->rating,
            'review' => $this->review->review,
            'message' => "{$this->student->name} rated your course '{$this->course->title}' {$this->review->rating} stars.",
        ];
    }
}
