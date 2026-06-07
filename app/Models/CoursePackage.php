<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePackage extends Model
{
    protected $fillable = [
        'course_id',
        'name',
        'price',
        'entitlements',
        'sort',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'entitlements' => 'array',
    ];

    /** Default feature set when a flag is missing from the JSON. */
    public const DEFAULT_ENTITLEMENTS = [
        'has_quizzes'     => false,
        'has_files'       => false,
        'has_certificate' => false,
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Normalised entitlements (always has every known key). */
    public function entitlementFlags(): array
    {
        return array_merge(self::DEFAULT_ENTITLEMENTS, $this->entitlements ?? []);
    }
}
