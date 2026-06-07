<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'digital_product_id',
        'payment_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    /** A completed, non-expired purchase grants access. */
    public function isActive(): bool
    {
        return $this->status === 'completed'
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }
}
