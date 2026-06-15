<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links standalone digital products to a bundle as bonus items.
 * is_included = false means explicitly excluded (used with 'selected' visibility mode).
 */
class BundleDigitalProduct extends Model
{
    protected $table = 'bundle_digital_products';

    protected $fillable = [
        'bundle_id',
        'digital_product_id',
        'is_included',
    ];

    protected $casts = [
        'is_included' => 'boolean',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function digitalProduct(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class);
    }
}
