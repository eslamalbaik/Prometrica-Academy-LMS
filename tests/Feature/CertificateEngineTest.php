<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Models\Module;
use App\Models\Lesson;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CertificateEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup certificate signing config
        Config::set('certificates.active_version', 'v1');
        Config::set('certificates.keys.v1', 'test-signing-key-secret-value');
        Config::set('certificates.keys.v2', 'test-signing-key-v2-rotated');
    }

    /**
     * Test certificate signature generation.
     */
    public function test_certificate_generates_versioned_hmac_signature_on_creation(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);

        $this->assertNotEmpty($certificate->ulid);
        $this->assertNotEmpty($certificate->signature);
        
        // Assert signature starts with version prefix
        $this->assertStringStartsWith('v1:', $certificate->signature);

        // Verify HMAC is correct
        $expected = Certificate::generateSignature('v1', $certificate->ulid, $user->id, $course->id);
        $this->assertEquals($expected, $certificate->signature);
    }

    /**
     * Test signature verification API endpoint with cryptographic validation.
     */
    public function test_verification_api_cryptographically_validates_signature(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);

        // 1. Test valid signature
        $response = $this->getJson("/api/certificates/{$certificate->ulid}/verify?" . http_build_query([
            'signature' => $certificate->signature,
            'userId'    => $user->id,
            'courseId'  => $course->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'valid'   => true,
            'student' => $user->name,
            'course'  => $course->title,
        ]);

        // 2. Test tampered signature (instantly rejects with 400 Bad Request)
        $tamperedSignature = $certificate->signature . 'tampered';
        $responseTampered = $this->getJson("/api/certificates/{$certificate->ulid}/verify?" . http_build_query([
            'signature' => $tamperedSignature,
            'userId'    => $user->id,
            'courseId'  => $course->id,
        ]));

        $responseTampered->assertStatus(400);
        $responseTampered->assertJson([
            'valid'   => false,
            'message' => 'Signature verification failed.',
        ]);
    }

    /**
     * Test verification API works with rotated signature version v2.
     */
    public function test_verification_api_validates_older_signature_versions_successfully(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        // Temporarily set active key to v2
        Config::set('certificates.active_version', 'v2');

        $certificate = Certificate::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);

        // Signature should start with v2:
        $this->assertStringStartsWith('v2:', $certificate->signature);

        // Verify using active key v2
        $response = $this->getJson("/api/certificates/{$certificate->ulid}/verify?" . http_build_query([
            'signature' => $certificate->signature,
            'userId'    => $user->id,
            'courseId'  => $course->id,
        ]));
        $response->assertStatus(200);

        // Now set active version back to v1 (key rotation simulations)
        Config::set('certificates.active_version', 'v1');

        // Verify v2 signature STILL succeeds because the key for v2 is still available
        $responseOlder = $this->getJson("/api/certificates/{$certificate->ulid}/verify?" . http_build_query([
            'signature' => $certificate->signature,
            'userId'    => $user->id,
            'courseId'  => $course->id,
        ]));
        $responseOlder->assertStatus(200);
    }

    /**
     * Test issuing certificate dispatches job asynchronously.
     */
    public function test_issuing_certificate_dispatches_generate_certificate_job(): void
    {
        Queue::fake();

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
            'slug' => 'test-lesson-' . uniqid(),
            'order' => 1
        ]);

        // Enroll user in course
        $user->enrolledCourses()->attach($course->id);

        // Mark lesson completed (makes progress 100%)
        $user->completedLessons()->attach($lesson->id, ['is_completed' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/student/courses/{$course->id}/certificate");

        $response->assertStatus(202);
        $response->assertJson([
            'status' => 'pending',
        ]);

        Queue::assertPushed(GenerateCertificateJob::class, function ($job) use ($user, $course) {
            // Check properties inside constructor/job
            $reflection = new \ReflectionClass($job);
            $userIdProp = $reflection->getProperty('userId');
            $userIdProp->setAccessible(true);
            $courseIdProp = $reflection->getProperty('courseId');
            $courseIdProp->setAccessible(true);

            return $userIdProp->getValue($job) === $user->id &&
                   $courseIdProp->getValue($job) === $course->id;
        });
    }

    /**
     * Test certificate download endpoint behaves correctly.
     */
    public function test_certificate_download_endpoint_requires_auth(): void
    {
        $response = $this->getJson("/api/v1/certificates/01ktgzhbyjfjsxa4g6fk41dtgw/download");
        $response->assertStatus(401);
    }

    public function test_certificate_download_endpoint_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/certificates/01ktgzhbyjfjsxa4g6fk41dtgw/download");
        $response->assertStatus(404);
    }

    public function test_certificate_download_endpoint_returns_403_if_unauthorized(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user1->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $user1->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/v1/certificates/{$certificate->ulid}/download");

        $response->assertStatus(403);
    }

    public function test_certificate_download_endpoint_succeeds_for_owner(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put("certificates/{$certificate->ulid}.pdf", "dummy pdf content");

        // Step 1: Request download URL
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/certificates/{$certificate->ulid}/download");

        $response->assertStatus(200);
        $response->assertJsonStructure(['download_url', 'expires_in']);

        $downloadUrl = $response->json('download_url');

        // Step 2: Hit the signed URL (no auth header needed!)
        $responseFile = $this->getJson($downloadUrl);
        $responseFile->assertStatus(200);
        $responseFile->assertHeader('Content-Type', 'application/pdf');
        $this->assertEquals('dummy pdf content', $responseFile->streamedContent());
    }

    public function test_certificate_download_endpoint_succeeds_for_admin(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $student = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $admin->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $student->id,
            'course_id' => $course->id,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put("certificates/{$certificate->ulid}.pdf", "dummy pdf content");

        // Step 1: Request download URL (as admin)
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/certificates/{$certificate->ulid}/download");

        $response->assertStatus(200);
        $response->assertJsonStructure(['download_url', 'expires_in']);

        $downloadUrl = $response->json('download_url');

        // Step 2: Hit the signed URL
        $responseFile = $this->getJson($downloadUrl);
        $responseFile->assertStatus(200);
        $responseFile->assertHeader('Content-Type', 'application/pdf');
        $this->assertEquals('dummy pdf content', $responseFile->streamedContent());
    }

    public function test_certificate_signed_download_returns_409_if_file_missing(): void
    {
        // Mock the storage disk to ensure it always reports the file as missing,
        // even if Browsershot runs successfully and attempts to write it.
        $mockDisk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $mockDisk->shouldReceive('exists')->andReturn(false);
        $mockDisk->shouldReceive('put')->andReturn(false);

        \Illuminate\Support\Facades\Storage::shouldReceive('disk')
            ->zeroOrMoreTimes()
            ->with('local')
            ->andReturn($mockDisk);

        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-' . uniqid(),
            'instructor_id' => $user->id,
        ]);

        $certificate = Certificate::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);

        // Request signed download URL
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/certificates/{$certificate->ulid}/download");

        $response->assertStatus(200);
        $downloadUrl = $response->json('download_url');

        // Verify hitting signedUrl returns 409 Conflict because storage file is missing
        $responseFile = $this->getJson($downloadUrl);
        $responseFile->assertStatus(409);
        $responseFile->assertJson([
            'status' => 'pending'
        ]);
    }
}
