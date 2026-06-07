<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'course_modules';

    protected $fillable = [
        'title', 'order', 'course_id'
    ];

    protected $appends = ['items'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'course_module_id')->orderBy('order');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'course_module_id')->orderBy('order');
    }

    public function attachments()
    {
        return $this->hasMany(ModuleAttachment::class)->orderBy('created_at');
    }

    public function getItemsAttribute()
    {
        $lessons = $this->lessons->map(function ($lesson) {
            $lesson->item_type = 'lesson';
            return $lesson;
        });

        $quizzes = $this->quizzes->map(function ($quiz) {
            $quiz->item_type = 'quiz';
            return $quiz;
        });

        return $lessons->concat($quizzes)->sortBy('order')->values();
    }
}
