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

        if (app()->environment('local') && ! app()->runningUnitTests()) {
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
            $req = request();
            $alerts = $req ? $req->attributes->get('simasadi_global_surveillance_alerts') : null;

            if ($alerts === null) {
                if (Schema::hasTable('lpks')) {
                    try {
                        $user = $req ? $req->user() : null;
                        $userId = $user ? $user->id : 'guest';

                        $computeAlerts = function () use ($user) {
                            $activeLpks = \App\Models\Lpk::accessibleBy($user)
                                ->with('assessments')
                                ->whereIn('status', ['ACTIVE', 'SUSPENDED', 'REVOKED'])
                                ->where(function ($q) {
                                    $q->whereNotNull('certificate_date')
                                      ->orWhereNotNull('expired_at');
                                })
                                ->get();

                            foreach ($activeLpks as $lpk) {
                                foreach ($lpk->assessments as $assessment) {
                                    $assessment->setRelation('lpk', $lpk);
                                }
                            }

                            $list = [];
                            foreach ($activeLpks as $lpk) {
                                foreach ($lpk->getActiveSurveillanceAlerts() as $alert) {
                                    $list[] = $alert;
                                }
                            }

                            // Convert Carbon instances to primitive ISO strings for safe serialization
                            return array_map(function ($item) {
                                foreach (['target_date', 'notice_date', 'last_notified_at'] as $dateKey) {
                                    if (!empty($item[$dateKey]) && $item[$dateKey] instanceof \Carbon\CarbonInterface) {
                                        $item[$dateKey] = $item[$dateKey]->toIso8601String();
                                    }
                                }
                                return $item;
                            }, $list);
                        };

                        $version = \Illuminate\Support\Facades\Cache::get('simasadi_surveillance_version', 1);
                        $cacheKey = "simasadi_alerts_{$userId}_v{$version}";

                        $rawAlerts = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($computeAlerts) {
                            return $computeAlerts();
                        });

                        // Rehydrate primitive ISO strings back to real Carbon instances
                        $alerts = array_map(function ($item) {
                            foreach (['target_date', 'notice_date', 'last_notified_at'] as $dateKey) {
                                if (!empty($item[$dateKey]) && is_string($item[$dateKey])) {
                                    $item[$dateKey] = \Illuminate\Support\Carbon::parse($item[$dateKey]);
                                }
                            }
                            return $item;
                        }, (array) $rawAlerts);
                    } catch (\Throwable $e) {
                        $alerts = [];
                    }
                } else {
                    $alerts = [];
                }

                if ($req) {
                    $req->attributes->set('simasadi_global_surveillance_alerts', $alerts);
                }
            }

            $view->with('globalSurveillanceAlerts', $alerts);
        });
    }
}
