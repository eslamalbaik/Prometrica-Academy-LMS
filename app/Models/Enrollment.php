<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'course_package_id', 'bundle_id',
        'progress', 'enrolled_at', 'expires_at',
        'device_id', 'device_ip', 'last_accessed_at',
        'max_devices', 'is_active',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'expires_at'  => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
        'max_devices' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function coursePackage()
    {
        return $this->belongsTo(CoursePackage::class);
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    /** Access is active when there is no expiry, or the expiry is still in the future. */
    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}
