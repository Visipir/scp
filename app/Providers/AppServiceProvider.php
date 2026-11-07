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
            $rawUsername = $request->input('username');
            $username = is_scalar($rawUsername) ? (string) $rawUsername : '';
            $key = Str::transliterate(Str::lower($username)).'|'.$request->ip();

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

        // Forgot password: 5 requests per hour per identifier/email + IP
        RateLimiter::for('forgot-password', function (Request $request) {
            $rawIdentifier = $request->input('identifier') ?: $request->input('email');
            $identifier = is_scalar($rawIdentifier) ? strtolower(trim((string) $rawIdentifier)) : '';
            $key = Str::transliterate($identifier) . '|' . $request->ip();

            return Limit::perHour(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak permintaan reset kata sandi. Silakan coba lagi dalam 1 jam.',
                    'errors'  => null,
                ], 429);
            });
        });
    }
}
