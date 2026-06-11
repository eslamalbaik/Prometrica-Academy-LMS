<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyPlan extends Model
{
    protected $fillable = [
        'user_id', 'enrollment_id', 'bundle_id', 'course_id',
        'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function tasks()
    {
        return $this->hasMany(StudyPlanTask::class)->orderBy('order');
    }

    // ─── Computed helpers ─────────────────────────────────────────────────────

    public function completedTasksCount(): int
    {
        return $this->tasks()->whereNotNull('completed_at')->count();
    }

    public function totalTasksCount(): int
    {
        return $this->tasks()->count();
    }

    public function progressPercentage(): int
    {
        $total = $this->totalTasksCount();
        return $total > 0 ? (int) round($this->completedTasksCount() / $total * 100) : 0;
    }
}
