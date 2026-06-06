<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class QuizAttempt extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'quiz_id', 'score', 'passed', 'answers', 'tenant_id'
    ];

    protected $casts = [
        'passed' => 'boolean',
        'answers' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
