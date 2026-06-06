<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_students',
        'active_courses',
        'total_enrollments',
        'total_revenue',
    ];
}
