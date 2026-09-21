<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));

        if (config('app.env') === 'production' || str_contains((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        if (config('database.default') === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if ($dbPath && ! str_contains($dbPath, ':memory:') && ! file_exists($dbPath)) {
                $dir = dirname($dbPath);
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @touch($dbPath);
            }
        }

        if (! app()->runningUnitTests()) {
            try {
                if (! Schema::hasTable('users')) {
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', ['--force' => true]);
                }
            } catch (\Throwable $e) {
                Log::warning('Auto migration and seeding skipped: '.$e->getMessage());
            }
        }

        view()->composer(['layouts.app', 'layouts.partials.topbar', 'dashboard'], function ($view) {
            if (Schema::hasTable('lpks')) {
                try {
                    $activeLpks = \App\Models\Lpk::with('assessments')
                        ->where('status', 'ACTIVE')
                        ->where(function ($q) {
                            $q->whereNotNull('certificate_date')
                              ->orWhereNotNull('expired_at');
                        })
                        ->get();

                    $alerts = [];
                    foreach ($activeLpks as $lpk) {
                        foreach ($lpk->getActiveSurveillanceAlerts() as $alert) {
                            $alerts[] = $alert;
                        }
                    }

                    $view->with('globalSurveillanceAlerts', $alerts);
                } catch (\Throwable $e) {
                    $view->with('globalSurveillanceAlerts', []);
                }
            } else {
                $view->with('globalSurveillanceAlerts', []);
            }
        });
    }
}
