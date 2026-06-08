<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Faq;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Option;
use App\Models\PricingPlan;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\SystemStatistic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. USERS ────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@prometrica.com'],
            [
                'name'              => 'Prometrica Admin',
                'password'          => bcrypt('Admin@2025!'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $students = [];
        $studentData = [
            ['name' => 'أحمد الشمري',   'email' => 'ahmed@demo.com'],
            ['name' => 'سارة المطيري',  'email' => 'sara@demo.com'],
            ['name' => 'محمد العتيبي',  'email' => 'mohammed@demo.com'],
            ['name' => 'نورة القحطاني', 'email' => 'noura@demo.com'],
            ['name' => 'فيصل الرشيدي', 'email' => 'faisal@demo.com'],
        ];
        foreach ($studentData as $s) {
            $students[] = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name'              => $s['name'],
                    'password'          => bcrypt('Student@2025!'),
                    'role'              => 'student',
                    'email_verified_at' => now(),
                ]
            );
        }

        // ─── 2. COURSES ──────────────────────────────────────────────────────────
        $coursesData = [
            [
                'slug'              => 'naplex-prep-2025',
                'title'             => 'NAPLEX Prep 2025 — الدورة الشاملة',
                'short_description' => 'الدورة الأكثر شمولاً للتحضير لامتحان NAPLEX باحترافية.',
                'description'       => 'تغطية كاملة لجميع أقسام NAPLEX: الصيدلانيات، الفارماكولوجيا، الرعاية الدوائية، والتفاعلات الدوائية.',
                'category'          => 'NAPLEX',
                'difficulty'        => 'advanced',
                'language'          => 'ar',
                'price'             => 299.00,
                'discount_price'    => 199.00,
                'is_free'           => false,
                'is_published'      => true,
                'status'            => 'published',
                'instructor_id'     => $admin->id,
                'access_days'       => 365,
            ],
            [
                'slug'              => 'fpgee-complete-course',
                'title'             => 'FPGEE — دورة الترخيص الأمريكي',
                'short_description' => 'التحضير الكامل لامتحان FPGEE للصيادلة الأجانب.',
                'description'       => 'برنامج متكامل يشمل الكيمياء الصيدلانية، الفارماكولوجيا، الكيمياء الحيوية، والتشريح.',
                'category'          => 'FPGEE',
                'difficulty'        => 'advanced',
                'language'          => 'ar',
                'price'             => 349.00,
                'discount_price'    => 249.00,
                'is_free'           => false,
                'is_published'      => true,
                'status'            => 'published',
                'instructor_id'     => $admin->id,
                'access_days'       => 365,
            ],
            [
                'slug'              => 'pharmacology-fundamentals',
                'title'             => 'أساسيات الفارماكولوجيا',
                'short_description' => 'مدخل شامل لعلم الأدوية للطلاب المبتدئين.',
                'description'       => 'دورة مجانية تغطي مبادئ الفارماكوكينيتيكس والفارماكوديناميكس والتصنيفات الدوائية الأساسية.',
                'category'          => 'Fundamentals',
                'difficulty'        => 'beginner',
                'language'          => 'ar',
                'price'             => 0,
                'discount_price'    => null,
                'is_free'           => true,
                'is_published'      => true,
                'status'            => 'published',
                'instructor_id'     => $admin->id,
                'access_days'       => null,
            ],
        ];

        $courses = [];
        foreach ($coursesData as $cd) {
            $courses[] = Course::firstOrCreate(['slug' => $cd['slug']], $cd);
        }

        // ─── 3. MODULES, LESSONS, QUIZZES ────────────────────────────────────────
        $modulesMap = [
            // NAPLEX course
            0 => [
                ['title' => 'الفارماكوكينيتيكس', 'lessons' => ['امتصاص الدواء', 'توزيع الدواء', 'استقلاب الدواء', 'إخراج الدواء']],
                ['title' => 'الفارماكوديناميكس', 'lessons' => ['آليات عمل الأدوية', 'العلاقة بين الجرعة والاستجابة', 'المستقبلات الدوائية']],
                ['title' => 'الرعاية الدوائية', 'lessons' => ['مراجعة الدواء', 'التفاعلات الدوائية', 'ضبط الجرعات في الفشل الكلوي']],
            ],
            // FPGEE course
            1 => [
                ['title' => 'الكيمياء الصيدلانية', 'lessons' => ['الروابط الكيميائية في الأدوية', 'التفاعلات الحيوية', 'علم الصيدلانيات']],
                ['title' => 'علم الأحياء الدقيقة والمناعة', 'lessons' => ['البكتيريا والفطريات', 'الفيروسات', 'المضادات الحيوية']],
            ],
            // Fundamentals course
            2 => [
                ['title' => 'مقدمة في علم الأدوية', 'lessons' => ['تعريف الفارماكولوجيا', 'تاريخ علم الأدوية', 'تصنيف الأدوية']],
                ['title' => 'المسارات الدوائية', 'lessons' => ['طرق إعطاء الدواء', 'أشكال الجرعات الصيدلانية']],
            ],
        ];

        $allModules = [];
        foreach ($courses as $ci => $course) {
            foreach ($modulesMap[$ci] as $mi => $moduleData) {
                $module = Module::firstOrCreate(
                    ['course_id' => $course->id, 'title' => $moduleData['title']],
                    ['order' => $mi + 1]
                );
                $allModules[] = $module;

                foreach ($moduleData['lessons'] as $li => $lessonTitle) {
                    Lesson::firstOrCreate(
                        ['course_module_id' => $module->id, 'title' => $lessonTitle],
                        ['order' => $li + 1, 'duration_seconds' => rand(600, 2400)]
                    );
                }
            }
        }

        // ─── 4. QUIZZES + QUESTIONS + OPTIONS ────────────────────────────────────
        $tenantId = 1;

        $quizData = [
            [
                'module_index' => 0, // الفارماكوكينيتيكس
                'title'        => 'اختبار الفارماكوكينيتيكس',
                'passing_score' => 70,
                'questions' => [
                    [
                        'text' => 'ما هي المعادلة الصحيحة لحساب نصف العمر البيولوجي (t½)؟',
                        'options' => [
                            ['text' => 't½ = 0.693 / Ke',            'correct' => true],
                            ['text' => 't½ = Ke / 0.693',            'correct' => false],
                            ['text' => 't½ = Vd × Ke',               'correct' => false],
                            ['text' => 't½ = CL / Vd',               'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'أي العوامل التالية يزيد من معدل امتصاص الدواء الفموي؟',
                        'options' => [
                            ['text' => 'تناول الدواء مع وجبة دهنية',  'correct' => false],
                            ['text' => 'تناول الدواء على معدة فارغة', 'correct' => true],
                            ['text' => 'ارتفاع درجة حرارة الجسم',     'correct' => false],
                            ['text' => 'زيادة حجم توزيع الدواء',     'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'يُعدّ حجم التوزيع (Vd) مؤشراً على:',
                        'options' => [
                            ['text' => 'سرعة استقلاب الدواء',                    'correct' => false],
                            ['text' => 'مدى انتشار الدواء في أنسجة الجسم',      'correct' => true],
                            ['text' => 'نسبة ارتباط الدواء بالبروتين',          'correct' => false],
                            ['text' => 'معدل إفراز الدواء عبر الكلى',           'correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'module_index' => 1, // الفارماكوديناميكس
                'title'        => 'اختبار الفارماكوديناميكس',
                'passing_score' => 70,
                'questions' => [
                    [
                        'text' => 'ما الفرق بين الناهض الكامل (Full Agonist) والناهض الجزئي (Partial Agonist)؟',
                        'options' => [
                            ['text' => 'الناهض الجزئي يُنتج استجابة قصوى أعلى',            'correct' => false],
                            ['text' => 'الناهض الكامل يُنتج استجابة قصوى أعلى من الجزئي', 'correct' => true],
                            ['text' => 'لا فرق بينهما في الفعالية',                         'correct' => false],
                            ['text' => 'الناهض الجزئي لا يرتبط بالمستقبل',                 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'الأدوية المضادة للكولين (Anticholinergics) تعمل عن طريق:',
                        'options' => [
                            ['text' => 'تنشيط مستقبلات الأسيتيلكولين',           'correct' => false],
                            ['text' => 'تثبيط مستقبلات الأسيتيلكولين (Muscarinic)', 'correct' => true],
                            ['text' => 'زيادة إفراز الأسيتيلكولين',              'correct' => false],
                            ['text' => 'تثبيط مستقبلات الأدرينالين',             'correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'module_index' => 4, // أساسيات - مقدمة
                'title'        => 'اختبار أساسيات الفارماكولوجيا',
                'passing_score' => 60,
                'questions' => [
                    [
                        'text' => 'ما هو الفارق بين الفارماكوكينيتيكس والفارماكوديناميكس؟',
                        'options' => [
                            ['text' => 'الفارماكوكينيتيكس = تأثير الدواء على الجسم، الفارماكوديناميكس = تأثير الجسم على الدواء', 'correct' => false],
                            ['text' => 'الفارماكوكينيتيكس = تأثير الجسم على الدواء، الفارماكوديناميكس = تأثير الدواء على الجسم', 'correct' => true],
                            ['text' => 'كلاهما يدرسان نفس الشيء',    'correct' => false],
                            ['text' => 'الفارماكوكينيتيكس يدرس التفاعلات الكيميائية فقط', 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'أي من التالي يُصنَّف كمضاد حيوي من مجموعة البيتا لاكتام؟',
                        'options' => [
                            ['text' => 'أزيثرومايسين',  'correct' => false],
                            ['text' => 'أموكسيسيللين',  'correct' => true],
                            ['text' => 'سيبروفلوكساسين', 'correct' => false],
                            ['text' => 'دوكسيسيكلين',   'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'مسار الإعطاء الذي يُتجاوز فيه المرور الأول عبر الكبد (First-Pass Effect) هو:',
                        'options' => [
                            ['text' => 'الفموي (Oral)',        'correct' => false],
                            ['text' => 'تحت اللسان (Sublingual)', 'correct' => true],
                            ['text' => 'الشرجي (Rectal) جزئياً', 'correct' => false],
                            ['text' => 'الوريدي (IV)',          'correct' => false],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($quizData as $qd) {
            $module = $allModules[$qd['module_index']] ?? null;
            if (!$module) continue;

            $quiz = Quiz::firstOrCreate(
                ['course_module_id' => $module->id, 'title' => $qd['title']],
                ['passing_score' => $qd['passing_score'], 'order' => 1, 'tenant_id' => $tenantId]
            );

            foreach ($qd['questions'] as $qOrder => $qInfo) {
                $question = Question::firstOrCreate(
                    ['question_text' => $qInfo['text'], 'tenant_id' => $tenantId],
                    ['order' => $qOrder + 1]
                );

                // Attach to quiz if not already attached
                if (!$quiz->questions()->where('question_id', $question->id)->exists()) {
                    $quiz->questions()->attach($question->id, ['order' => $qOrder + 1]);
                }

                foreach ($qInfo['options'] as $option) {
                    Option::firstOrCreate(
                        ['question_id' => $question->id, 'option_text' => $option['text']],
                        ['is_correct' => $option['correct']]
                    );
                }
            }
        }

        // ─── 5. COURSE PACKAGES ──────────────────────────────────────────────────
        foreach ([$courses[0], $courses[1]] as $course) {
            $packages = [
                [
                    'name'  => 'الأساسي',
                    'price' => $course->discount_price ?? $course->price,
                    'sort'  => 1,
                    'entitlements' => ['has_quizzes' => false, 'has_files' => false, 'has_certificate' => false],
                ],
                [
                    'name'  => 'المتقدم',
                    'price' => ($course->discount_price ?? $course->price) + 50,
                    'sort'  => 2,
                    'entitlements' => ['has_quizzes' => true, 'has_files' => true, 'has_certificate' => false],
                ],
                [
                    'name'  => 'البريميوم',
                    'price' => ($course->discount_price ?? $course->price) + 100,
                    'sort'  => 3,
                    'entitlements' => ['has_quizzes' => true, 'has_files' => true, 'has_certificate' => true],
                ],
            ];
            foreach ($packages as $pkg) {
                CoursePackage::firstOrCreate(
                    ['course_id' => $course->id, 'name' => $pkg['name']],
                    ['price' => $pkg['price'], 'sort' => $pkg['sort'], 'entitlements' => $pkg['entitlements']]
                );
            }
        }

        // ─── 6. PRICING PLANS ────────────────────────────────────────────────────
        $plans = [
            [
                'name'           => 'الأساسي',
                'name_en'        => 'Basic',
                'description'    => 'مثالي للبدء والتعرف على المنصة',
                'description_en' => 'Perfect for getting started',
                'price'          => 99.00,
                'period'         => 'شهرياً',
                'period_en'      => 'per month',
                'badge'          => null,
                'badge_en'       => null,
                'is_featured'    => false,
                'is_active'      => true,
                'cta_label'      => 'ابدأ الآن',
                'cta_label_en'   => 'Get Started',
                'cta_url'        => '/register',
                'color'          => '#6B7280',
                'sort'           => 1,
                'features' => [
                    [
                        'title'    => 'التعليم',
                        'title_en' => 'Education',
                        'items'    => [
                            ['text' => 'الوصول لـ 3 دورات', 'text_en' => 'Access to 3 courses', 'included' => true],
                            ['text' => 'اختبارات تفاعلية', 'text_en' => 'Interactive quizzes',  'included' => true],
                            ['text' => 'شهادة إتمام',       'text_en' => 'Completion certificate', 'included' => false],
                            ['text' => 'دعم مباشر 24/7',   'text_en' => '24/7 live support',    'included' => false],
                        ],
                    ],
                ],
            ],
            [
                'name'           => 'المحترف',
                'name_en'        => 'Professional',
                'description'    => 'الأنسب للتحضير الجاد لامتحانات الترخيص',
                'description_en' => 'Best for serious exam preparation',
                'price'          => 199.00,
                'period'         => 'شهرياً',
                'period_en'      => 'per month',
                'badge'          => 'الأكثر طلباً',
                'badge_en'       => 'Most Popular',
                'is_featured'    => true,
                'is_active'      => true,
                'cta_label'      => 'ابدأ الآن',
                'cta_label_en'   => 'Get Started',
                'cta_url'        => '/register',
                'color'          => '#10B981',
                'sort'           => 2,
                'features' => [
                    [
                        'title'    => 'التعليم',
                        'title_en' => 'Education',
                        'items'    => [
                            ['text' => 'الوصول لجميع الدورات', 'text_en' => 'Access to all courses',    'included' => true],
                            ['text' => 'اختبارات تفاعلية',     'text_en' => 'Interactive quizzes',       'included' => true],
                            ['text' => 'شهادة إتمام',           'text_en' => 'Completion certificate',   'included' => true],
                            ['text' => 'دعم مباشر 24/7',       'text_en' => '24/7 live support',         'included' => true],
                            ['text' => 'ملفات تحميل',           'text_en' => 'Downloadable files',        'included' => true],
                        ],
                    ],
                ],
            ],
            [
                'name'           => 'الشامل',
                'name_en'        => 'Elite',
                'description'    => 'تجربة تعليمية متكاملة مع ضمان النجاح',
                'description_en' => 'Complete learning experience with success guarantee',
                'price'          => 349.00,
                'period'         => 'شهرياً',
                'period_en'      => 'per month',
                'badge'          => 'الأفضل قيمة',
                'badge_en'       => 'Best Value',
                'is_featured'    => false,
                'is_active'      => true,
                'cta_label'      => 'ابدأ الآن',
                'cta_label_en'   => 'Get Started',
                'cta_url'        => '/register',
                'color'          => '#F59E0B',
                'sort'           => 3,
                'features' => [
                    [
                        'title'    => 'التعليم',
                        'title_en' => 'Education',
                        'items'    => [
                            ['text' => 'الوصول لجميع الدورات + الجديدة', 'text_en' => 'All courses + new releases', 'included' => true],
                            ['text' => 'اختبارات تفاعلية غير محدودة',    'text_en' => 'Unlimited quizzes',          'included' => true],
                            ['text' => 'شهادات رسمية معتمدة',            'text_en' => 'Official certificates',      'included' => true],
                            ['text' => 'دعم مباشر أولوية',               'text_en' => 'Priority live support',      'included' => true],
                            ['text' => 'ملفات تحميل + كتب رقمية',        'text_en' => 'Files + digital books',      'included' => true],
                            ['text' => 'جلسات مباشرة مع المدربين',       'text_en' => 'Live sessions with coaches', 'included' => true],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::firstOrCreate(
                ['name_en' => $plan['name_en']],
                $plan
            );
        }

        // ─── 7. ENROLLMENTS ──────────────────────────────────────────────────────
        // أول 3 طلاب في الدورة المجانية، أول طالبين في NAPLEX
        foreach (array_slice($students, 0, 3) as $s) {
            Enrollment::firstOrCreate(
                ['user_id' => $s->id, 'course_id' => $courses[2]->id],
                ['progress' => rand(10, 80)]
            );
        }
        foreach (array_slice($students, 0, 2) as $s) {
            Enrollment::firstOrCreate(
                ['user_id' => $s->id, 'course_id' => $courses[0]->id],
                ['progress' => rand(5, 50)]
            );
        }
        Enrollment::firstOrCreate(
            ['user_id' => $students[2]->id, 'course_id' => $courses[1]->id],
            ['progress' => rand(20, 70)]
        );

        // ─── 8. REVIEWS ──────────────────────────────────────────────────────────
        $reviews = [
            ['student' => 0, 'course' => 2, 'rating' => 5, 'review' => 'دورة ممتازة، شرح واضح ومبسط. أنصح بها كل مبتدئ في الصيدلة.'],
            ['student' => 1, 'course' => 2, 'rating' => 5, 'review' => 'المحتوى منظم جداً والأمثلة عملية. استفدت كثيراً.'],
            ['student' => 2, 'course' => 2, 'rating' => 4, 'review' => 'دورة جيدة، تمنيت مزيداً من الاختبارات التطبيقية.'],
            ['student' => 0, 'course' => 0, 'rating' => 5, 'review' => 'أفضل دورة NAPLEX في المنصة! حضّرتني بشكل احترافي.'],
            ['student' => 1, 'course' => 0, 'rating' => 5, 'review' => 'المحتوى شامل والاختبارات مشابهة للامتحان الفعلي.'],
        ];

        foreach ($reviews as $r) {
            CourseReview::firstOrCreate(
                ['user_id' => $students[$r['student']]->id, 'course_id' => $courses[$r['course']]->id],
                ['rating' => $r['rating'], 'review' => $r['review'], 'is_approved' => true]
            );
        }

        // ─── 9. FAQs ─────────────────────────────────────────────────────────────
        $faqs = [
            [
                'question'    => 'هل يمكنني الوصول للدورات من أي جهاز؟',
                'question_en' => 'Can I access courses from any device?',
                'answer'      => 'نعم، المنصة تدعم جميع الأجهزة — الحاسوب والهاتف والتابلت.',
                'answer_en'   => 'Yes, our platform supports all devices — desktop, mobile and tablet.',
                'sort'        => 1,
            ],
            [
                'question'    => 'هل الشهادات الممنوحة معتمدة؟',
                'question_en' => 'Are the certificates accredited?',
                'answer'      => 'شهاداتنا معتمدة ومعترف بها في المجال الصيدلاني المهني.',
                'answer_en'   => 'Our certificates are recognized in the pharmaceutical professional field.',
                'sort'        => 2,
            ],
            [
                'question'    => 'ما مدة الوصول للدورة بعد الشراء؟',
                'question_en' => 'How long do I have access after purchase?',
                'answer'      => 'يعتمد على الدورة — معظم الدورات المدفوعة تمنح وصولاً لمدة سنة كاملة.',
                'answer_en'   => 'It depends on the course — most paid courses grant one year of access.',
                'sort'        => 3,
            ],
            [
                'question'    => 'هل يوجد ضمان استرداد المبلغ؟',
                'question_en' => 'Is there a money-back guarantee?',
                'answer'      => 'نعم، نوفر ضمان استرداد كامل خلال 7 أيام من تاريخ الشراء.',
                'answer_en'   => 'Yes, we offer a full refund within 7 days of purchase.',
                'sort'        => 4,
            ],
            [
                'question'    => 'هل تتوفر دورات مجانية للتجربة؟',
                'question_en' => 'Are there free courses to try?',
                'answer'      => 'نعم! لدينا دورات مجانية تتيح لك استكشاف المنصة قبل الاشتراك.',
                'answer_en'   => 'Yes! We have free courses so you can explore the platform before subscribing.',
                'sort'        => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(
                ['question_en' => $faq['question_en']],
                array_merge($faq, ['is_active' => true])
            );
        }

        // ─── 10. SYSTEM STATISTICS ───────────────────────────────────────────────
        $totalStudents   = User::where('role', 'student')->count();
        $activeCourses   = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::count();

        SystemStatistic::updateOrCreate(
            ['id' => 1],
            [
                'total_students'    => $totalStudents,
                'active_courses'    => $activeCourses,
                'total_enrollments' => $totalEnrollments,
                'total_revenue'     => 0,
            ]
        );

        $this->command->info('✅ Seeder completed successfully!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',   'admin@prometrica.com', 'Admin@2025!'],
                ['Student', 'ahmed@demo.com',       'Student@2025!'],
                ['Student', 'sara@demo.com',        'Student@2025!'],
                ['Student', 'mohammed@demo.com',    'Student@2025!'],
            ]
        );
    }
}
