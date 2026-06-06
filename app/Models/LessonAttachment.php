<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonAttachment extends Model
{
    protected $fillable = ['lesson_id', 'title', 'file_path', 'file_size', 'download_count'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
