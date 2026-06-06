<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => bcrypt('admin'),
            'role' => 'admin',
        ]);

        // Student User
        $student = User::factory()->create([
            'name' => 'Student User',
            'email' => 'client@demo.com',
            'password' => bcrypt('client'),
            'role' => 'student',
        ]);

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
            'module_id' => $module->id,
            'title' => 'Absorption and Distribution',
            'duration' => 15,
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
