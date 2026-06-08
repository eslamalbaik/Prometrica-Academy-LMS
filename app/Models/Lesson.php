<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id', 'title', 'video_url', 'content', 'order', 'duration_seconds'
    ];

    protected $appends = ['duration_minutes'];

    /** Duration in whole minutes (rounded up), for the frontend sidebar. */
    public function getDurationMinutesAttribute(): int
    {
        return $this->duration_seconds ? (int) ceil($this->duration_seconds / 60) : 0;
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'course_module_id');
    }

    public function attachments()
    {
        return $this->hasMany(LessonAttachment::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('is_completed', 'completed_at')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(LessonComment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }
}
