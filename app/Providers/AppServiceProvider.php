<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // ──────────────────────────────────────────────
        // Production Security Hardening
        // ──────────────────────────────────────────────

        // Force HTTPS if running behind a TLS-terminating reverse proxy
        if ($this->app->environment('production') && config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        // Strict model behavior: prevent lazy loading & silently discarding fills
        Model::shouldBeStrict(! $this->app->isProduction());

        // In production: prevent lazy loading but log instead of throwing
        if ($this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                logger()->warning("Lazy loading violation: {$model}::{$relation}");
            });
        }

        // Prevent N+1 queries in development
        if ($this->app->environment('local')) {
            DB::listen(function ($query) {
                if ($query->time > 500) {
                    logger()->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time . 'ms',
                    ]);
                }
            });
        }

        // Default password rules for all validation
        // Note: ->uncompromised() removed — requires internet (offline system)
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers();
        });
    }
}
