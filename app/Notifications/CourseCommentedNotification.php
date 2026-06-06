<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonComment;

class CourseCommentedNotification extends Notification
{
    use Queueable;

    protected $student;
    protected $course;
    protected $lesson;
    protected $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $student, Course $course, Lesson $lesson, LessonComment $comment)
    {
        $this->student = $student;
        $this->course = $course;
        $this->lesson = $lesson;
        $this->comment = $comment;
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
            'type' => 'course_commented',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->title,
            'comment' => substr($this->comment->body, 0, 100),
            'message' => "{$this->student->name} commented on lesson '{$this->lesson->title}' in '{$this->course->title}'.",
        ];
    }
}
