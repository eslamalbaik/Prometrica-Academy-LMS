<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question', 'question_en', 'answer', 'answer_en', 'is_active', 'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
