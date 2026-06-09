<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseReview;
use App\Models\Certificate;
use App\Models\DigitalProduct;
use App\Models\DigitalProductFile;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Option;
use App\Models\ProductPurchase;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Comprehensive demo data covering EVERY admin-dashboard section so the whole
 * system can be exercised end-to-end. Idempotent: safe to run repeatedly
 * (uses firstOrCreate / existence checks — won't duplicate rows).
 *
 * Run:  php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private string $bunnyVideo = 'https://player.mediadelivery.net/play/679277/d957c1bd-1913-40cf-aa28-930e8f45643b';

    public function run(): void
    {
        $admin    = $this->seedUsers();
        $students = User::where('role', 'student')->get();
        $courses  = $this->seedCourses($admin->id);
        $this->seedQuestionBank();
        $this->seedQuizzes($courses);
        $this->seedPackages($courses->first());
        $this->seedEnrollmentsAndProgress($students, $courses);
        $this->seedReviews($students, $courses);
        $this->seedCertificates($students, $courses);
        $this->seedDigitalProducts($students);
        $this->seedNotifications($admin);

        $this->command->info('✅ Demo data seeded for all sections.');
    }

    private function seedUsers(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            ['name' => 'Admin User', 'password' => Hash::make('admin'), 'role' => 'admin'],
        );

        $names = ['Eslam Albaik', 'Layla Hassan', 'Omar Saleh', 'Sara Mahmoud', 'Yousef Khaled', 'Nour Adel', 'Ahmad Fares', 'Mariam Tariq'];
        foreach ($names as $i => $name) {
            User::firstOrCreate(
                ['email' => 'student' . ($i + 1) . '@demo.com'],
                ['name' => $name, 'password' => Hash::make('password'), 'role' => 'student'],
            );
        }

        return $admin;
    }

    private function seedCourses(int $instructorId)
    {
        $defs = [
            ['Pharmacology 101', 'Foundations of pharmacology for licensing exams.', 299.99, false, null, 'Prometric'],
            ['Clinical Pharmacy Essentials', 'Evidence-based clinical decision making.', 199.00, false, 365, 'Clinical'],
            ['Free Starter Pack', 'Sample lessons to get you started.', 0, true, null, 'Foundation'],
            ['Mock Exam Mastery', 'Realistic exam simulations and strategies.', 149.50, false, 180, 'Exams'],
        ];

        $courses = collect();
        foreach ($defs as $i => [$title, $desc, $price, $isFree, $accessDays, $category]) {
            $course = Course::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'instructor_id' => $instructorId,
                    'title'         => $title,
                    'description'   => $desc,
                    'short_description' => $desc,
                    'price'         => $price,
                    'is_free'       => $isFree,
                    'access_days'   => $accessDays,
                    'category'      => $category,
                    'difficulty'    => ['Beginner', 'Intermediate', 'Advanced'][$i % 3],
                    'language'      => 'English',
                    'is_published'  => true,
                    'status'        => 'Published',
                ],
            );

            // 2 modules, each with 2 lessons (first lesson gets the Bunny video).
            for ($m = 1; $m <= 2; $m++) {
                $module = Module::firstOrCreate(
                    ['course_id' => $course->id, 'order' => $m],
                    ['title' => "Module {$m}: " . ($m === 1 ? 'Fundamentals' : 'Applied Practice')],
                );
                for ($l = 1; $l <= 2; $l++) {
                    Lesson::firstOrCreate(
                        ['course_module_id' => $module->id, 'order' => $l],
                        [
                            'title'     => "Lesson {$m}.{$l}",
                            'video_url' => ($m === 1 && $l === 1) ? $this->bunnyVideo : '',
                            'content'   => 'Lesson content and notes go here.',
                        ],
                    );
                }
            }
            $courses->push($course->fresh());
        }

        return $courses;
    }

    private function seedQuestionBank(): void
    {
        $bank = [
            ['Which organ is primarily responsible for drug metabolism?', [['The liver', true], ['The heart', false], ['The lungs', false], ['The skin', false]]],
            ['What does "bioavailability" measure?', [['Fraction of dose reaching circulation', true], ['Drug color', false], ['Tablet size', false], ['Shelf life', false]]],
            ['First-pass metabolism mainly occurs in the?', [['Liver', true], ['Kidney', false], ['Spleen', false], ['Pancreas', false]]],
            ['Which route avoids first-pass metabolism?', [['Intravenous', true], ['Oral', false], ['Rectal partially', false], ['Buccal none', false]]],
            ['Half-life of a drug refers to?', [['Time for concentration to halve', true], ['Time to dissolve', false], ['Time to absorb fully', false], ['Expiry duration', false]]],
            ['A loading dose is used to?', [['Reach therapeutic level quickly', true], ['Reduce side effects', false], ['Lower the price', false], ['Extend half-life', false]]],
        ];

        foreach ($bank as $idx => [$text, $options]) {
            $q = Question::firstOrCreate(
                ['question_text' => $text],
                ['tenant_id' => 'default', 'order' => $idx],
            );
            if ($q->options()->count() === 0) {
                foreach ($options as $j => [$optText, $correct]) {
                    Option::create([
                        'question_id' => $q->id,
                        'option_text' => $optText,
                        'is_correct'  => $correct,
                    ]);
                }
            }
        }
    }

    private function seedQuizzes($courses): void
    {
        $questionIds = Question::orderBy('id')->pluck('id')->all();

        foreach ($courses as $course) {
            $module = $course->modules()->orderBy('order')->first();
            if (! $module) {
                continue;
            }

            $quiz = Quiz::firstOrCreate(
                ['course_module_id' => $module->id, 'title' => $course->title . ' — Final Quiz'],
                ['passing_score' => 60, 'order' => 99, 'tenant_id' => 'default'],
            );

            // Attach 4 bank questions to the quiz via the pivot.
            if (DB::table('quiz_questions')->where('quiz_id', $quiz->id)->count() === 0) {
                foreach (array_slice($questionIds, 0, 4) as $order => $qid) {
                    DB::table('quiz_questions')->insert([
                        'quiz_id'     => $quiz->id,
                        'question_id' => $qid,
                        'order'       => $order,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }

    private function seedPackages(?Course $course): void
    {
        if (! $course) {
            return;
        }

        $tiers = [
            ['Basic',  49,  ['has_quizzes' => false, 'has_files' => false, 'has_certificate' => false], 1],
            ['Silver', 99,  ['has_quizzes' => true,  'has_files' => true,  'has_certificate' => false], 2],
            ['Gold',   149, ['has_quizzes' => true,  'has_files' => true,  'has_certificate' => true],  3],
        ];
        foreach ($tiers as [$name, $price, $ent, $sort]) {
            CoursePackage::firstOrCreate(
                ['course_id' => $course->id, 'name' => $name],
                ['price' => $price, 'entitlements' => $ent, 'sort' => $sort],
            );
        }
    }

    private function seedEnrollmentsAndProgress($students, $courses): void
    {
        foreach ($students as $i => $student) {
            // Each student enrolls in 1-3 courses.
            foreach ($courses->take(($i % 3) + 1) as $j => $course) {
                $expires = $course->access_days
                    ? now()->addDays($course->access_days)
                    : null;

                // Make one student's access already expired (to test that state).
                if ($i === 0 && $j === 1) {
                    $expires = now()->subDays(2);
                }

                Enrollment::firstOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    [
                        'progress'    => [0, 35, 70, 100][($i + $j) % 4],
                        'enrolled_at' => now()->subDays(rand(1, 40)),
                        'expires_at'  => $expires,
                    ],
                );
            }
        }
    }

    private function seedReviews($students, $courses): void
    {
        $samples = [
            [5, 'Outstanding course, passed my exam on the first try!'],
            [4, 'Very practical and well structured.'],
            [5, 'The instructors explain everything clearly.'],
            [4, 'Great content, would recommend to colleagues.'],
        ];
        foreach ($courses as $c => $course) {
            foreach ($students->take(3) as $s => $student) {
                [$rating, $review] = $samples[($c + $s) % count($samples)];
                CourseReview::firstOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    ['rating' => $rating, 'review' => $review, 'is_approved' => true],
                );
            }
        }
    }

    private function seedCertificates($students, $courses): void
    {
        // Issue certificates to students who "completed" (progress 100) a course.
        $completed = Enrollment::where('progress', 100)->get();
        foreach ($completed as $enrollment) {
            Certificate::firstOrCreate([
                'user_id'   => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
            ]);
        }
        // Guarantee at least one certificate exists.
        if (Certificate::count() === 0 && $students->isNotEmpty() && $courses->isNotEmpty()) {
            Certificate::firstOrCreate([
                'user_id'   => $students->first()->id,
                'course_id' => $courses->first()->id,
            ]);
        }
    }

    private function seedDigitalProducts($students): void
    {
        $defs = [
            ['Pharmacology Cheat Sheet (eBook)', 'A concise 40-page reference PDF.', 19.99, false, null],
            ['Drug Interaction Templates', 'Editable templates for clinical use.', 9.99, false, 90],
            ['Free Study Planner', 'A printable weekly study planner.', 0, true, null],
        ];

        foreach ($defs as $i => [$title, $desc, $price, $isFree, $accessDays]) {
            $product = DigitalProduct::firstOrCreate(
                ['title' => $title],
                [
                    'description' => $desc,
                    'price'       => $price,
                    'is_active'   => true,
                    'is_free'     => $isFree,
                    'access_days' => $accessDays,
                ],
            );

            // One placeholder file record per product (path points to private disk).
            DigitalProductFile::firstOrCreate(
                ['digital_product_id' => $product->id, 'file_name' => 'sample-' . ($i + 1) . '.pdf'],
                ['file_path' => "digital_products/{$product->id}/sample.pdf", 'file_size' => 102400],
            );
        }

        // Give the first two students a purchase each.
        $products = DigitalProduct::orderBy('id')->take(2)->get();
        foreach ($students->take(2) as $k => $student) {
            $product = $products[$k] ?? $products->first();
            if (! $product) {
                continue;
            }
            ProductPurchase::firstOrCreate(
                ['user_id' => $student->id, 'digital_product_id' => $product->id],
                [
                    'status'     => 'completed',
                    'expires_at' => $product->access_days ? now()->addDays($product->access_days) : null,
                ],
            );
        }
    }

    private function seedNotifications(User $admin): void
    {
        if (DB::table('notifications')->where('notifiable_id', $admin->id)->count() > 0) {
            return;
        }

        $events = [
            ['course_rated', ['type' => 'course_rated', 'student_name' => 'Layla Hassan', 'course_title' => 'Pharmacology 101', 'rating' => 5]],
            ['course_commented', ['type' => 'course_commented', 'student_name' => 'Omar Saleh', 'course_title' => 'Clinical Pharmacy Essentials', 'lesson_title' => 'Lesson 1.1']],
            ['course_completed', ['type' => 'course_completed', 'student_name' => 'Sara Mahmoud', 'course_title' => 'Free Starter Pack']],
        ];

        foreach ($events as $i => [$type, $data]) {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => 'App\\Notifications\\' . Str::studly($type) . 'Notification',
                'notifiable_type' => User::class,
                'notifiable_id'   => $admin->id,
                'data'            => json_encode($data),
                'read_at'         => $i === 0 ? now() : null,
                'created_at'      => now()->subHours($i + 1),
                'updated_at'      => now()->subHours($i + 1),
            ]);
        }
    }
}
