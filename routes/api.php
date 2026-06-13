<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\LandingCourseController;
use App\Http\Controllers\Api\StudentLessonController;

Route::get('/landing/courses', [LandingCourseController::class, 'index']);
Route::get('/landing/courses/{id}', [LandingCourseController::class, 'show']);

// Public digital-products storefront (marketing-safe fields only)
Route::get('/landing/digital-products', [\App\Http\Controllers\Api\LandingDigitalProductController::class, 'index']);
Route::get('/landing/digital-products/{product}', [\App\Http\Controllers\Api\LandingDigitalProductController::class, 'show']);

// Public pricing plans (active only)
Route::get('/landing/pricing-plans', function () {
    return response()->json(
        \App\Models\PricingPlan::where('is_active', true)->orderBy('sort')->orderBy('id')->get()
    );
});

// Public FAQs (active only)
Route::get('/landing/faqs', function () {
    return response()->json(
        \App\Models\Faq::where('is_active', true)->orderBy('sort')->orderBy('id')->get()
    );
});

// Public bundles (active only)
Route::get('/landing/bundles', [\App\Http\Controllers\Api\BundleController::class, 'landing']);

// ─── Public certificate routes ───────────────────────────────────────────────
Route::get('/certificates/{ulid}/verify',  [\App\Http\Controllers\Api\CertificateController::class, 'verify']);


// FTR-005: Student study plans
Route::middleware('auth:sanctum')->prefix('v1/student')->group(function () {
    Route::get('/study-plans',                           [\App\Http\Controllers\Api\StudyPlanController::class, 'index']);
    Route::post('/study-plans/unsubscribe',              [\App\Http\Controllers\Api\StudyPlanController::class, 'unsubscribeEmails']);
    Route::post('/study-plans/subscribe',                [\App\Http\Controllers\Api\StudyPlanController::class, 'subscribeEmails']);
    Route::post('/study-plan-tasks/{task}/toggle',       [\App\Http\Controllers\Api\StudyPlanController::class, 'toggleTask']);
});

Route::middleware('auth:sanctum')->prefix('student')->group(function () {
    Route::get('/my-courses', [\App\Http\Controllers\Api\StudentController::class, 'myCourses']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\StudentController::class, 'dashboard']);
    Route::put('/profile', [\App\Http\Controllers\Api\StudentController::class, 'updateProfile']);
    Route::put('/password', [\App\Http\Controllers\Api\StudentController::class, 'updatePassword']);
    Route::get('/courses/{id}/learn', [\App\Http\Controllers\Api\StudentController::class, 'showCourse']);
    Route::post('/courses/{id}/reviews', [\App\Http\Controllers\Api\StudentController::class, 'submitReview']);
    Route::post('/lessons/{id}/complete', [\App\Http\Controllers\Api\StudentController::class, 'completeLesson']);
    Route::post('/lessons/{id}/comments', [\App\Http\Controllers\Api\StudentController::class, 'submitComment']);
    Route::get('/lessons/{lesson}', [StudentLessonController::class, 'show']);
    Route::get('/attachments/{attachment}/download', [StudentLessonController::class, 'downloadAttachment']);
    // Quizzes
    Route::get('/quizzes/{id}', [\App\Http\Controllers\Api\QuizController::class, 'showStudentQuiz']);
    Route::post('/quizzes/{id}/submit', [\App\Http\Controllers\Api\QuizController::class, 'submitStudentQuiz']);
    // Certificates
    Route::get('/certificates', [\App\Http\Controllers\Api\CertificateController::class, 'index']);
    Route::post('/courses/{id}/certificate', [\App\Http\Controllers\Api\CertificateController::class, 'issue']);
    Route::post('/certificates/{ulid}/regenerate', [\App\Http\Controllers\Api\CertificateController::class, 'studentRegenerate']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/courses/{id}/enroll', [\App\Http\Controllers\Api\EnrollmentController::class, 'enroll']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('dashboard')->group(function () {
    Route::get('/stats',       [DashboardController::class, 'index']);

    Route::apiResource('courses', \App\Http\Controllers\Api\CourseController::class);

    // Quizzes
    Route::get('/quizzes', [\App\Http\Controllers\Api\QuizController::class, 'index']);
    Route::post('/quizzes', [\App\Http\Controllers\Api\QuizController::class, 'store']);
    Route::put('/quizzes/{id}', [\App\Http\Controllers\Api\QuizController::class, 'update']);
    Route::delete('/quizzes/{id}', [\App\Http\Controllers\Api\QuizController::class, 'destroy']);

    // Students
    Route::get('/students',            [\App\Http\Controllers\Api\UserController::class, 'students']);
    Route::post('/students',           [\App\Http\Controllers\Api\UserController::class, 'store']);
    Route::get('/students/{id}/detail',[\App\Http\Controllers\Api\UserController::class, 'studentDetail']);

    // Enrollments
    Route::get('/enrollments',                               [\App\Http\Controllers\Api\UserController::class, 'enrollments']);
    Route::post('/enrollments',                              [\App\Http\Controllers\Api\UserController::class, 'adminEnroll']);
    Route::post('/enrollments/{id}/reset-device',            [\App\Http\Controllers\Api\UserController::class, 'resetDeviceLock']);

    // Course content builders
    Route::post('/modules',           [\App\Http\Controllers\Api\CourseModuleController::class, 'store']);
    Route::put('/modules/reorder',    [\App\Http\Controllers\Api\CourseModuleController::class, 'reorder']);
    Route::put('/modules/{id}',       [\App\Http\Controllers\Api\CourseModuleController::class, 'update']);
    Route::delete('/modules/{id}',    [\App\Http\Controllers\Api\CourseModuleController::class, 'destroy']);

    Route::post('/lessons',           [\App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::put('/lessons/reorder',    [\App\Http\Controllers\Api\LessonController::class, 'reorder']);
    Route::put('/lessons/{id}',       [\App\Http\Controllers\Api\LessonController::class, 'update']);
    Route::delete('/lessons/{id}',    [\App\Http\Controllers\Api\LessonController::class, 'destroy']);

    // Module PDF attachments
    Route::get('/modules/{id}/attachments',    [\App\Http\Controllers\Api\ModuleAttachmentController::class, 'index']);
    Route::post('/modules/{id}/attachments',   [\App\Http\Controllers\Api\ModuleAttachmentController::class, 'store']);
    Route::delete('/attachments/{id}',         [\App\Http\Controllers\Api\ModuleAttachmentController::class, 'destroy']);

    // Lesson PDF attachments
    Route::get('/lessons/{id}/attachments',           [\App\Http\Controllers\Api\LessonAttachmentController::class, 'index']);
    Route::post('/lessons/{id}/attachments',          [\App\Http\Controllers\Api\LessonAttachmentController::class, 'store']);
    Route::delete('/lesson-attachments/{id}',         [\App\Http\Controllers\Api\LessonAttachmentController::class, 'destroy']);

    // Certificates (admin)
    Route::get('/certificates',        [\App\Http\Controllers\Api\CertificateController::class, 'adminIndex']);
    Route::get('/certificates/stats',  [\App\Http\Controllers\Api\CertificateController::class, 'adminStats']);
    Route::post('/certificates/issue',                    [\App\Http\Controllers\Api\CertificateController::class, 'adminIssue']);
    Route::post('/certificates/{ulid}/regenerate',        [\App\Http\Controllers\Api\CertificateController::class, 'adminRegenerate']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/mark-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
});

Route::middleware(['auth:sanctum', 'role:admin', 'tenant', 'throttle:analytics'])->prefix('v1/dashboard')->group(function () {
    Route::get('/quizzes/{id}/summary', [\App\Http\Controllers\Api\QuizController::class, 'getQuizSummary']);
    Route::get('/quizzes/attempts/history', [\App\Http\Controllers\Api\QuizController::class, 'getQuizAttemptsHistory']);
});

Route::prefix('v1/auth')->group(function () {
    // Email auth (login, register, logout)
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Password reset
    Route::post('/forgot-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword']);
});

// Google OAuth code exchange (called from frontend callback page)
Route::post('/auth/google/exchange', [\App\Http\Controllers\AuthController::class, 'exchangeGoogleCode'])
    ->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'role:admin', 'tenant'])->prefix('v1/tenant')->group(function () {
    Route::apiResource('questions', \App\Http\Controllers\Api\QuestionBankController::class);
    Route::post('/quizzes/{id}/sync-questions', [\App\Http\Controllers\Api\QuestionBankController::class, 'syncQuestions']);
});

// ─── Digital Products: Admin CRUD + secure files + sales ─────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('dashboard')->group(function () {
    Route::get('/digital-products',               [\App\Http\Controllers\Api\DigitalProductController::class, 'index']);
    Route::post('/digital-products',              [\App\Http\Controllers\Api\DigitalProductController::class, 'store']);
    Route::get('/digital-products/{product}',     [\App\Http\Controllers\Api\DigitalProductController::class, 'show']);
    Route::post('/digital-products/{product}',    [\App\Http\Controllers\Api\DigitalProductController::class, 'update']); // multipart
    Route::delete('/digital-products/{product}',  [\App\Http\Controllers\Api\DigitalProductController::class, 'destroy']);
    Route::get('/digital-products/{product}/sales', [\App\Http\Controllers\Api\DigitalProductController::class, 'sales']);

    // Private vault file management
    Route::post('/digital-products/{product}/files', [\App\Http\Controllers\Api\DigitalProductFileController::class, 'store']);
    Route::delete('/digital-product-files/{file}',   [\App\Http\Controllers\Api\DigitalProductFileController::class, 'destroy']);

    // Global pricing plans (landing packages)
    Route::get('/pricing-plans',           [\App\Http\Controllers\Api\PricingPlanController::class, 'index']);
    Route::post('/pricing-plans',          [\App\Http\Controllers\Api\PricingPlanController::class, 'store']);
    Route::put('/pricing-plans/{plan}',    [\App\Http\Controllers\Api\PricingPlanController::class, 'update']);
    Route::delete('/pricing-plans/{plan}', [\App\Http\Controllers\Api\PricingPlanController::class, 'destroy']);

    // FAQs
    Route::get('/faqs',         [\App\Http\Controllers\Api\FaqController::class, 'index']);
    Route::post('/faqs',        [\App\Http\Controllers\Api\FaqController::class, 'store']);
    Route::put('/faqs/{faq}',   [\App\Http\Controllers\Api\FaqController::class, 'update']);
    Route::delete('/faqs/{faq}', [\App\Http\Controllers\Api\FaqController::class, 'destroy']);

    // Course packages (tiered entitlements)
    Route::get('/courses/{course}/packages',  [\App\Http\Controllers\Api\CoursePackageController::class, 'index']);
    Route::post('/courses/{course}/packages', [\App\Http\Controllers\Api\CoursePackageController::class, 'store']);
    Route::put('/course-packages/{package}',  [\App\Http\Controllers\Api\CoursePackageController::class, 'update']);
    Route::delete('/course-packages/{package}', [\App\Http\Controllers\Api\CoursePackageController::class, 'destroy']);

    // Bundles (multi-course packages)
    Route::get('/bundles',              [\App\Http\Controllers\Api\BundleController::class, 'index']);
    Route::post('/bundles',             [\App\Http\Controllers\Api\BundleController::class, 'store']);
    Route::put('/bundles/{bundle}',     [\App\Http\Controllers\Api\BundleController::class, 'update']);
    Route::delete('/bundles/{bundle}',  [\App\Http\Controllers\Api\BundleController::class, 'destroy']);

    // FTR-005: Admin study plan management
    Route::get('/students/{userId}/study-plans',   [\App\Http\Controllers\Api\StudyPlanController::class, 'adminIndex']);
    Route::put('/study-plan-tasks/{task}',          [\App\Http\Controllers\Api\StudyPlanController::class, 'updateTask']);
});

// ─── Digital Products: Student library + entitlement download request ────────
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Profile avatar (shared: students + admins)
    Route::post('/profile/avatar', [\App\Http\Controllers\Api\ProfileAvatarController::class, 'update']);
    Route::delete('/profile/avatar', [\App\Http\Controllers\Api\ProfileAvatarController::class, 'destroy']);

    // Video heartbeat (anti-cheat progress)
    Route::post('/progress/ping', [\App\Http\Controllers\Api\VideoProgressController::class, 'ping']);
    // Short-lived token-signed HLS/video URL (Bunny)
    Route::get('/lessons/{id}/video-token', [\App\Http\Controllers\Api\VideoTokenController::class, 'issue']);

    Route::get('/my-library', [\App\Http\Controllers\Api\MyLibraryController::class, 'index']);
    Route::get('/my-subscriptions', [\App\Http\Controllers\Api\MySubscriptionsController::class, 'index']);
    Route::post('/digital-products/{product}/purchase',
        [\App\Http\Controllers\Api\DigitalPurchaseController::class, 'store']);
    Route::get('/digital-products/{product}/files/{file}/download',
        [\App\Http\Controllers\Api\DigitalDownloadController::class, 'requestDownload']);
    Route::get('/certificates/{certificate:ulid}/download', [\App\Http\Controllers\Api\CertificateController::class, 'download']);

    // Bundle purchase (student)
    Route::post('/bundles/{bundle}/purchase', [\App\Http\Controllers\Api\BundleController::class, 'purchase']);
});

// ─── Digital Products: signed file serving (NO auth — signature is the proof) ─
Route::get('/v1/digital-products/files/{file}/serve',
    [\App\Http\Controllers\Api\DigitalDownloadController::class, 'serve'])
    ->middleware('signed')
    ->name('digital-products.files.serve');

// ─── Certificates: signed file serving (NO auth — signature is the proof) ─
Route::prefix('v1')->group(function () {
    Route::get('certificates/{certificate:ulid}/signed-download',
        [\App\Http\Controllers\Api\CertificateController::class, 'signedDownload'])
        ->middleware('signed')
        ->name('certificate.signed_download');
});



