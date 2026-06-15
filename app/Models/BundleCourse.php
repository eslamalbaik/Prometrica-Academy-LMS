<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pivot model for bundle_course with permission columns.
 * Allows per-course overrides of bundle-level visibility defaults.
 */
class BundleCourse extends Model
{
    protected $table = 'bundle_course';

    protected $fillable = [
        'bundle_id',
        'course_id',
        'sort',
        'quiz_visibility',
        'certificate_enabled',
        'products_visibility',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quizPermissions(): HasMany
    {
        return $this->hasMany(BundleQuizPermission::class, 'bundle_course_id');
    }
}
