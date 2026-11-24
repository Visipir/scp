<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), accelerometer=(), focus-without-user-activation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType === '' || str_contains($contentType, 'text/html')) {
            $connectSrc = "'self'";
            if (app()->isLocal() && file_exists(public_path('hot'))) {
                $connectSrc .= ' http://localhost:5173 ws://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173 http://[::1]:5173 ws://[::1]:5173';
            }

            // Alpine.js (standard runtime) evaluates directives with new Function(), so 'unsafe-eval'
            // remains alongside per-request nonces and 'strict-dynamic' to block injected inline scripts.
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' 'unsafe-eval' 'report-sample'",
                "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline'",
                "style-src-elem 'self' 'nonce-{$nonce}'",
                "style-src-attr 'unsafe-inline'",
                "img-src 'self' data: blob: https://upload.wikimedia.org",
                "font-src 'self' data:",
                "connect-src {$connectSrc}",
                "object-src 'none'",
                "base-uri 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
