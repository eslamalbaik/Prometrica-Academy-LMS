<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name'     => 'Admin User',
                'password' => bcrypt('admin'),
                'role'     => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Student User
        $student = User::firstOrCreate(
            ['email' => 'client@demo.com'],
            [
                'name'     => 'Student User',
                'password' => bcrypt('client'),
                'role'     => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Demo Course
        $course = \App\Models\Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Pharmacology 101',
            'description' => 'Introduction to basic pharmacology.',
            'price' => 299.99,
            'is_published' => true,
            'slug' => 'pharmacology-101',
        ]);

        // Demo Module
        $module = \App\Models\Module::create([
            'course_id' => $course->id,
            'title' => 'Basics of Pharmacokinetics',
            'order' => 1,
        ]);

        // Demo Lesson
        \App\Models\Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Absorption and Distribution',
            'order' => 1,
        ]);

        // Demo Enrollment
        \App\Models\Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'progress' => 0,
        ]);
    }
}
