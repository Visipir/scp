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

        // API chat: 10 requests/minute and 100 requests/day per authenticated user (or IP).
        RateLimiter::for('api-chat', function (Request $request) {
            $userKey = (string) ($request->user()?->id ?: $request->ip());

            return [
                Limit::perMinute(10)->by($userKey . '-minute')->response(function () {
                    return response()->json([
                        'message' => 'Terlalu banyak permintaan chat. Tunggu 1 menit sebelum mengirim pesan lagi.',
                        'errors'  => null,
                    ], 429);
                }),
                Limit::perDay(100)->by($userKey . '-day')->response(function () {
                    return response()->json([
                        'message' => 'Batas kuota harian chat (100 pesan/hari) tercapai. Silakan coba lagi besok.',
                        'errors'  => null,
                    ], 429);
                }),
            ];
        });
    }
}
