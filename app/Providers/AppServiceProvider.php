<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $forceHttps = filter_var(
            env('APP_FORCE_HTTPS', $this->app->environment('production')),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($forceHttps) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if ($this->app->environment('production')) {
            // Aiven Fix: Disable primary key requirement at session level for migrations/seeds
            try {
                \Illuminate\Support\Facades\DB::statement('SET SESSION sql_require_primary_key = 0;');
            } catch (\Exception $e) {
                // Ignore if the statement fails (e.g. database doesn't support it)
            }
        }

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            \Debugbar::enable();
        } else {
            \Debugbar::disable();
        }
    }
}
