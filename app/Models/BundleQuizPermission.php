<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Controls which quizzes are visible when quiz_visibility = 'selected'
 * on either the bundle or a specific bundle_course row.
 */
class BundleQuizPermission extends Model
{
    protected $table = 'bundle_quiz_permissions';

    protected $fillable = [
        'bundle_id',
        'bundle_course_id',
        'quiz_id',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function bundleCourse(): BelongsTo
    {
        return $this->belongsTo(BundleCourse::class, 'bundle_course_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
