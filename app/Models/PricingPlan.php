<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $fillable = [
        'name', 'name_en', 'description', 'description_en',
        'price', 'period', 'period_en',
        'badge', 'badge_en', 'is_featured', 'is_active',
        'cta_label', 'cta_label_en', 'cta_url',
        'features', 'sort', 'color',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
        'features'    => 'array',
    ];

    /**
     * Feature matrix shape (bilingual, grouped):
     * [
     *   { "title": "التدريب والتعليم", "title_en": "Training",
     *     "items": [ { "text": "...", "text_en": "...", "included": true } ] }
     * ]
     */
    public const DEFAULT_FEATURES = [];
}
