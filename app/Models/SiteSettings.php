<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $fillable = [
        'show_bundles',
        'show_programs',
        'show_courses',
        'show_trust_section',
        'show_digital_products',
        'show_testimonials',
        'show_faq',
        'contact_whatsapp',
        'contact_telegram',
        'contact_twitter',
        'contact_email',
    ];

    protected $casts = [
        'show_bundles' => 'boolean',
        'show_programs' => 'boolean',
        'show_courses' => 'boolean',
        'show_trust_section' => 'boolean',
        'show_digital_products' => 'boolean',
        'show_testimonials' => 'boolean',
        'show_faq' => 'boolean',
    ];

    public static function getInstance()
    {
        return self::firstOrCreate([], [
            'show_bundles' => true,
            'show_programs' => false,
            'show_courses' => false,
            'show_trust_section' => false,
            'show_digital_products' => true,
            'show_testimonials' => true,
            'show_faq' => true,
        ]);
    }
}
