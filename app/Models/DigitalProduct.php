<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalProduct extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'thumbnail_path',
        'is_active',
        'is_free',
        'access_days',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
        'is_free'   => 'boolean',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(DigitalProductFile::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ProductPurchase::class);
    }
}
