<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalProductFolder extends Model
{
    protected $fillable = [
        'digital_product_id',
        'parent_folder_id',
        'name',
        'description',
        'order',
    ];

    public function product()
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    public function parentFolder()
    {
        return $this->belongsTo(DigitalProductFolder::class, 'parent_folder_id');
    }

    public function childFolders()
    {
        return $this->hasMany(DigitalProductFolder::class, 'parent_folder_id')->orderBy('order');
    }

    public function files()
    {
        return $this->hasMany(DigitalProductFile::class, 'folder_id')->orderBy('order');
    }
}
