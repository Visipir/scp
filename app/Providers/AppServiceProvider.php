<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // API login: 5 attempts per minute per username + IP.
        RateLimiter::for('api-login', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('username'))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
