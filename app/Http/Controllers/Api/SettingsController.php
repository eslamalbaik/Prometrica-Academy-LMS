<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getPublic()
    {
        $settings = SiteSettings::getInstance();

        return response()->json([
            'show_bundles' => $settings->show_bundles,
            'show_programs' => $settings->show_programs,
            'show_courses' => $settings->show_courses,
            'show_trust_section' => $settings->show_trust_section,
            'show_digital_products' => $settings->show_digital_products,
            'show_testimonials' => $settings->show_testimonials,
            'show_faq' => $settings->show_faq,
            'contact_whatsapp' => $settings->contact_whatsapp,
            'contact_telegram' => $settings->contact_telegram,
            'contact_twitter' => $settings->contact_twitter,
            'contact_email' => $settings->contact_email,
        ]);
    }

    public function getAdmin()
    {
        $this->authorize('viewAny', SiteSettings::class);

        $settings = SiteSettings::getInstance();
        return response()->json($settings);
    }

    public function updateAdmin(Request $request)
    {
        $settings = SiteSettings::getInstance();
        $this->authorize('update', $settings);

        $validated = $request->validate([
            'show_bundles' => 'boolean',
            'show_programs' => 'boolean',
            'show_courses' => 'boolean',
            'show_trust_section' => 'boolean',
            'show_digital_products' => 'boolean',
            'show_testimonials' => 'boolean',
            'show_faq' => 'boolean',
            'contact_whatsapp' => 'nullable|string|url',
            'contact_telegram' => 'nullable|string|url',
            'contact_twitter' => 'nullable|string|url',
            'contact_email' => 'nullable|email',
        ]);

        $settings->update($validated);

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => $settings,
        ]);
    }
}
