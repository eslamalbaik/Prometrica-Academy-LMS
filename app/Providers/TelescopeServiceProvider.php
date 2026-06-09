<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TelescopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            return;
        }
    }

    public function boot(): void
    {
        if (! class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        $isLocal = $this->app->environment('local');

        \Laravel\Telescope\Telescope::filter(function (\Laravel\Telescope\IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });

        \Laravel\Telescope\Telescope::hideRequestParameters(['_token']);

        \Laravel\Telescope\Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);

        Gate::define('viewTelescope', function (User $user) {
            return in_array($user->email, []);
        });
    }
}
