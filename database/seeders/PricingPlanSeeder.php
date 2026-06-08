<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing pricing plans first to ensure a clean seed
        PricingPlan::query()->delete();

        $groups = function (array $courses, array $quizzes, array $files, array $addons) {
            return [
                ['title' => 'الكورسات والمدة', 'title_en' => 'Courses & Duration', 'items' => $courses],
                ['title' => 'الاختبارات والكويزات', 'title_en' => 'Quizzes & Exams', 'items' => $quizzes],
                ['title' => 'الملفات والمرفقات', 'title_en' => 'Files & Attachments', 'items' => $files],
                ['title' => 'الدعم والإضافات', 'title_en' => 'Support & Addons', 'items' => $addons],
            ];
        };

        $f = fn ($ar, $en, $inc = true) => ['text' => $ar, 'text_en' => $en, 'included' => $inc];

        $plans = [
            [
                'name' => 'الباقة الاحترافية',
                'name_en' => 'Professional Plan',
                'description' => 'الحل الشامل للمنصات الكبيرة والشركات مع حماية قصوى وتطبيق هاتف.',
                'description_en' => 'The comprehensive solution for large platforms, corporate training, and high-volume academies.',
                'price' => 9000,
                'period' => 'ريال / سنوياً',
                'period_en' => 'SAR / yearly',
                'badge' => 'المؤسسات الكبرى',
                'badge_en' => 'Enterprise',
                'is_featured' => false,
                'is_active' => true,
                'sort' => 1,
                'color' => '#7c3aed', // Purple/Violet
                'cta_label' => 'اشترك الآن',
                'cta_label_en' => 'Subscribe Now',
                'cta_url' => '/register',
                'features' => $groups(
                    [
                        $f('كورسات تدريبية غير محدودة', 'Unlimited active courses'),
                        $f('مدة الاشتراك: صلاحية سنوية مرنة', 'Subscription: Flexible annual access'),
                        $f('عدد الطلاب: غير محدود', 'Trainees limit: Unlimited'),
                    ],
                    [
                        $f('كويزات واختبارات دورية غير محدودة', 'Unlimited quizzes & checkpoints'),
                        $f('بنك الأسئلة ومحاكاة الامتحانات الشاملة', 'Full question bank & exam simulators'),
                    ],
                    [
                        $f('رفع ملفات ومذكرات (مساحة غير محدودة)', 'Upload PDFs, notes & files (unlimited space)'),
                        $f('حماية قصوى وتشفير للفيديو ضد التسريب والسرقة', 'Enterprise video encryption & screen capture prevention'),
                    ],
                    [
                        $f('دعم فني مباشر ومسؤول حساب مخصص', 'Dedicated account manager & instant support'),
                        $f('ربط نطاق مخصص وتخصيص الهوية بالكامل', 'Custom domain & full white-label branding'),
                    ]
                ),
            ],
            [
                'name' => 'الباقة الأساسية',
                'name_en' => 'Essential Plan',
                'description' => 'مخصصة للمؤسسات المتوسطة والأكاديميات المتنامية والدورات التعليمية.',
                'description_en' => 'Perfect for growing academies, professional trainers, and specialized schools.',
                'price' => 5400,
                'period' => 'ريال / سنوياً',
                'period_en' => 'SAR / yearly',
                'badge' => 'الأكثر شيوعاً',
                'badge_en' => 'Most Popular',
                'is_featured' => true,
                'is_active' => true,
                'sort' => 2,
                'color' => '#0284c7', // Sky Blue
                'cta_label' => 'اشترك الآن',
                'cta_label_en' => 'Subscribe Now',
                'cta_url' => '/register',
                'features' => $groups(
                    [
                        $f('حتى 30 كورس نشط بالمنصة', 'Up to 30 active courses'),
                        $f('مدة الاشتراك: صلاحية سنوية كاملة', 'Subscription: Full annual access'),
                        $f('عدد الطلاب: حتى 1,000 طالب نشط', 'Trainees limit: Up to 1,000 active'),
                    ],
                    [
                        $f('كويزات واختبارات دورية غير محدودة', 'Unlimited quizzes & checkpoints'),
                        $f('بنك الأسئلة ومحاكاة الامتحانات الشاملة', 'Full question bank & exam simulators'),
                    ],
                    [
                        $f('رفع ملفات ومرفقات (حتى 10 جيجابايت)', 'Upload PDFs, notes & files (up to 10 GB)'),
                        $f('حماية متقدمة للفيديو ضد التسريب والتحميل', 'Advanced anti-leak & anti-download video protection'),
                    ],
                    [
                        $f('دعم فني سريع على مدار الساعة', '24/7 priority email & chat support'),
                        $f('ربط نطاق مخصص (Custom Domain)', 'Custom domain link integration'),
                    ]
                ),
            ],
            [
                'name' => 'الباقة المجانية',
                'name_en' => 'Free Plan',
                'description' => 'مثالية لصناع المحتوى والمدربين الجدد للبدء بدون أي تكاليف.',
                'description_en' => 'Perfect for individual creators, hobbyists, and new academies starting at zero cost.',
                'price' => 0,
                'period' => 'مجاناً',
                'period_en' => 'Free',
                'badge' => 'البداية السريعة',
                'badge_en' => 'Quick Start',
                'is_featured' => false,
                'is_active' => true,
                'sort' => 3,
                'color' => '#64748b', // Slate gray
                'cta_label' => 'ابدأ مجاناً',
                'cta_label_en' => 'Start Free',
                'cta_url' => '/register',
                'features' => $groups(
                    [
                        $f('حتى 3 كورسات نشطة بالمنصة', 'Up to 3 active courses'),
                        $f('مدة الاشتراك: تجربة مجانية دائمة', 'Subscription: Forever free access'),
                        $f('عدد الطلاب: حتى 50 طالب نشط', 'Trainees limit: Up to 50 active'),
                    ],
                    [
                        $f('كويزات واختبارات دورية مبسطة لكل درس', 'Basic quizzes per lesson'),
                        $f('بنك الأسئلة ومحاكاة الامتحانات الشاملة', 'Full question bank & exam simulators', false),
                    ],
                    [
                        $f('رفع ملفات ومرفقات (حتى 50 ميجابايت)', 'Upload PDFs, notes & files (up to 50 MB)'),
                        $f('حماية متقدمة للفيديو ضد التسريب والتحميل', 'Advanced anti-leak video protection', false),
                    ],
                    [
                        $f('دعم فني أساسي عبر البريد الإلكتروني', 'Basic email support'),
                        $f('ربط نطاق مخصص (Custom Domain)', 'Custom domain link integration', false),
                    ]
                ),
            ],
        ];

        foreach ($plans as $p) {
            PricingPlan::create($p);
        }
    }
}
