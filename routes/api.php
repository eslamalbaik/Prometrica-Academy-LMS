<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\API\LandingCourseController;
use App\Http\Controllers\API\StudentLessonController;

Route::get('/landing/courses', [LandingCourseController::class, 'index']);
Route::get('/landing/courses/{id}', [LandingCourseController::class, 'show']);

// ─── Public certificate verification ────────────────────────────────────────
Route::get('/certificates/{uuid}/verify', [\App\Http\Controllers\Api\CertificateController::class, 'verify']);

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
    Route::get('/students',    [\App\Http\Controllers\Api\UserController::class, 'students']);
    Route::post('/students',   [\App\Http\Controllers\Api\UserController::class, 'store']);

    // Enrollments
    Route::get('/enrollments', [\App\Http\Controllers\Api\UserController::class, 'enrollments']);
    Route::post('/enrollments',[\App\Http\Controllers\Api\UserController::class, 'adminEnroll']);

    // Course content builders
    Route::post('/modules',         [\App\Http\Controllers\API\CourseModuleController::class, 'store']);
    Route::put('/modules/{id}',     [\App\Http\Controllers\API\CourseModuleController::class, 'update']);
    Route::delete('/modules/{id}',  [\App\Http\Controllers\API\CourseModuleController::class, 'destroy']);

    Route::post('/lessons',         [\App\Http\Controllers\API\LessonController::class, 'store']);
    Route::put('/lessons/{id}',     [\App\Http\Controllers\API\LessonController::class, 'update']);
    Route::delete('/lessons/{id}',  [\App\Http\Controllers\API\LessonController::class, 'destroy']);

    // Certificates (admin)
    Route::get('/certificates',       [\App\Http\Controllers\Api\CertificateController::class, 'adminIndex']);
    Route::get('/certificates/stats', [\App\Http\Controllers\Api\CertificateController::class, 'adminStats']);
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
    Route::post('/forgot-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum', 'role:admin', 'tenant'])->prefix('v1/tenant')->group(function () {
    Route::apiResource('questions', \App\Http\Controllers\Api\QuestionBankController::class);
    Route::post('/quizzes/{id}/sync-questions', [\App\Http\Controllers\Api\QuestionBankController::class, 'syncQuestions']);
});


