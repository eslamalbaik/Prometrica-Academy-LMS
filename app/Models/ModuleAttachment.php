<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleAttachment extends Model
{
    protected $fillable = ['module_id', 'title', 'file_path', 'file_size'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
