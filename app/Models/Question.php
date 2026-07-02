<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Question extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'question_text', 'explanation', 'image_path', 'order',
    ];

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }
}
