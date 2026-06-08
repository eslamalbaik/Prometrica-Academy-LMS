<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_updates_lesson_duration_if_null_or_zero(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        $module = Module::create([
            'title' => 'Test Module',
            'course_id' => $course->id,
            'order' => 1
        ]);

        $lesson = Lesson::create([
            'title' => 'Test Lesson',
            'course_module_id' => $module->id,
            'video_url' => 'https://example.com/video.mp4',
            'order' => 1,
            'duration_seconds' => null, // Start with null
        ]);

        // 1. Ping with duration 120.5. Should save as 121 (rounded up)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/progress/ping', [
                'lesson_id' => $lesson->id,
                'current_time' => 10,
                'duration' => 120.5,
            ]);

        $response->assertStatus(200);
        $lesson->refresh();
        $this->assertEquals(121, $lesson->duration_seconds);
        $this->assertEquals(3, $lesson->duration_minutes); // 121 / 60 rounded up is 3 minutes

        // 2. Ping again with a different duration. Since duration_seconds is already set, it should not change.
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/progress/ping', [
                'lesson_id' => $lesson->id,
                'current_time' => 25,
                'duration' => 300,
            ]);

        $response->assertStatus(200);
        $lesson->refresh();
        $this->assertEquals(121, $lesson->duration_seconds); // remains 121
    }
}
