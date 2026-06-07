<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'short_description', 'description', 'thumbnail', 
        'category', 'difficulty', 'language', 'meta_title', 'meta_description', 
        'keywords', 'is_free', 'price', 'discount_price', 'include_in_subscription',
        'status', 'is_published', 'instructor_id', 'access_days'
    ];

    protected $appends = ['total_duration', 'lessons_count', 'average_rating'];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function packages()
    {
        return $this->hasMany(CoursePackage::class)->orderBy('sort');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')->withPivot('progress')->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(CourseReview::class);
    }

    public function getTotalDurationAttribute()
    {
        return $this->modules->flatMap->lessons->sum('duration_minutes');
    }

    public function getLessonsCountAttribute()
    {
        return $this->modules->flatMap->lessons->count();
    }

    public function getAverageRatingAttribute()
    {
        // Use the eager-loaded relation when available to avoid an N+1 query
        // per course in list endpoints; fall back to a single aggregate query.
        if ($this->relationLoaded('reviews')) {
            return round($this->reviews->where('is_approved', true)->avg('rating') ?? 0, 1);
        }

        return round($this->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
    }
}
