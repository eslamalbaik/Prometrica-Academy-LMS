<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyPlanTask extends Model
{
    protected $fillable = [
        'study_plan_id', 'lesson_id', 'quiz_id',
        'title', 'type', 'scheduled_date', 'completed_at', 'order',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at'   => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function studyPlan()
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
