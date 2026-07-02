<?php

namespace Database\Seeders;

use App\Models\Bundle;
use Illuminate\Database\Seeder;

class BundleSeeder extends Seeder
{
    public function run(): void
    {
        // Only create if bundles don't exist
        if (Bundle::count() > 0) {
            return;
        }

        Bundle::create([
            'name' => 'الباقة الفضية',
            'name_en' => 'Silver Package',
            'description' => 'باقة أساسية للمبتدئين مع محاضرات وأسئلة',
            'description_en' => 'Basic package for beginners with lectures and questions',
            'price' => 299,
            'access_days' => 90,
            'type' => 'standard',
            'is_featured' => false,
            'badge' => 'الأساسية',
            'badge_en' => 'Basic',
            'color' => '#64748B',
            'cta_label' => 'اشترك الآن',
            'cta_label_en' => 'Subscribe Now',
            'period' => 'لـ 3 أشهر',
            'period_en' => '3 months',
        ]);

        Bundle::create([
            'name' => 'الباقة الذهبية',
            'name_en' => 'Gold Package',
            'description' => 'باقة متقدمة مع محاضرات وأسئلة شاملة وشهادة',
            'description_en' => 'Advanced package with comprehensive lectures, questions and certificate',
            'price' => 599,
            'access_days' => 180,
            'type' => 'premium',
            'is_featured' => true,
            'badge' => 'الأكثر شهرة',
            'badge_en' => 'Most Popular',
            'color' => '#F59E0B',
            'cta_label' => 'اشترك الآن',
            'cta_label_en' => 'Subscribe Now',
            'period' => 'لـ 6 أشهر',
            'period_en' => '6 months',
        ]);

        Bundle::create([
            'name' => 'الباقة البلاتينية',
            'name_en' => 'Platinum Package',
            'description' => 'باقة شاملة مع جميع الموارد والدعم المباشر والمرافقة الشخصية',
            'description_en' => 'Complete package with all resources, direct support and personal mentoring',
            'price' => 999,
            'access_days' => 365,
            'type' => 'platinum',
            'is_featured' => false,
            'badge' => 'VIP',
            'badge_en' => 'VIP',
            'color' => '#8B5CF6',
            'cta_label' => 'اشترك الآن',
            'cta_label_en' => 'Subscribe Now',
            'period' => 'سنة كاملة',
            'period_en' => '1 year',
        ]);
    }
}
