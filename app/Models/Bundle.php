<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $fillable = [
        'name', 'name_en', 'description', 'description_en',
        'price', 'image', 'is_active', 'sort', 'access_days',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'price'       => 'float',
        'access_days' => 'integer',
    ];

    /** Courses included in this bundle */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'bundle_course');
    }

    /** Enrollments generated from purchasing this bundle */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
