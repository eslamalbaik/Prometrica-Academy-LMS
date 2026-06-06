<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('analytics', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Course::observe(\App\Observers\CourseObserver::class);
        \App\Models\Enrollment::observe(\App\Observers\EnrollmentObserver::class);
        \App\Models\CourseReview::observe(\App\Observers\CourseReviewObserver::class);
        \App\Models\LessonComment::observe(\App\Observers\LessonCommentObserver::class);
    }
}
