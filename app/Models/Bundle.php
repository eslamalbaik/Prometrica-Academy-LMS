<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    protected $fillable = [
        'name', 'name_en', 'description', 'description_en',
        'price', 'image', 'is_active', 'sort', 'access_days',
        'type', 'max_courses', 'max_devices', 'auto_renewal',
        'default_quiz_visibility', 'default_certificate_enabled', 'default_products_visibility',
        // Display fields (pricing card UI)
        'badge', 'badge_en', 'is_featured', 'color',
        'cta_label', 'cta_label_en', 'period', 'period_en', 'features',
    ];

    protected $casts = [
        'is_active'                    => 'boolean',
        'is_featured'                  => 'boolean',
        'price'                        => 'float',
        'access_days'                  => 'integer',
        'max_courses'                  => 'integer',
        'max_devices'                  => 'integer',
        'auto_renewal'                 => 'boolean',
        'default_certificate_enabled'  => 'boolean',
        'features'                     => 'array',
    ];

    /** Courses in this bundle (with permission pivot columns) */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'bundle_course')
                    ->withPivot('id', 'sort', 'quiz_visibility', 'certificate_enabled', 'products_visibility')
                    ->orderByPivot('sort');
    }

    /** Explicit BundleCourse rows (needed for permission sub-queries) */
    public function bundleCourses(): HasMany
    {
        return $this->hasMany(BundleCourse::class);
    }

    /** Enrollments generated from this bundle */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Quiz permission overrides for 'selected' visibility */
    public function quizPermissions(): HasMany
    {
        return $this->hasMany(BundleQuizPermission::class);
    }

    /** Digital products attached as bonus items to this bundle */
    public function digitalProducts(): BelongsToMany
    {
        return $this->belongsToMany(DigitalProduct::class, 'bundle_digital_products')
                    ->withPivot('is_included')
                    ->withTimestamps();
    }

    /** Helper: is this bundle still active for a given user enrollment */
    public function computeExpiresAt(): ?\Carbon\Carbon
    {
        return $this->access_days ? now()->addDays($this->access_days) : null;
    }
}
