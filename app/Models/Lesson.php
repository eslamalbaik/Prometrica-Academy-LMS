<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id', 'title', 'video_url', 'content', 'order'
    ];

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
