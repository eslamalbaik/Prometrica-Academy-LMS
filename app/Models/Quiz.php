<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Quiz extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'course_module_id', 'title', 'passing_score', 'order', 'tenant_id'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class, 'course_module_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('quiz_questions.order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
