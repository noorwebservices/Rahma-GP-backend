<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Forcer HTTPS pour toutes les URLs générées en production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Anti-bruteforce sur la connexion : par identifiant + IP, et par IP globale.
        RateLimiter::for('login', function (Request $request) {
            $identifier = (string) ($request->input('login')
                ?? $request->input('email')
                ?? $request->input('telephone')
                ?? '');

            return [
                Limit::perMinute(5)->by(mb_strtolower($identifier).'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
