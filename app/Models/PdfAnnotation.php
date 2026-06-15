<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfAnnotation extends Model
{
    protected $fillable = [
        'user_id',
        'digital_product_file_id',
        'page',
        'type',
        'selected_text',
        'note',
        'color',
        'position',
    ];

    protected $casts = [
        'page'     => 'integer',
        'position' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(DigitalProductFile::class, 'digital_product_file_id');
    }
}
