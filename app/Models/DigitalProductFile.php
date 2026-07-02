<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalProductFile extends Model
{
    protected $fillable = [
        'digital_product_id',
        'folder_id',
        'file_name',
        'file_path',
        'file_size',
        'order',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DigitalProductFolder::class, 'folder_id');
    }
}
