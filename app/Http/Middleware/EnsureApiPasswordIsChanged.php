<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API mirror of EnsurePasswordIsChanged. While must_change_password is set the
 * user may only log in, read their profile, change the password, or log out.
 */
class EnsureApiPasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $user->isProtectedAccount()) {
            $allowed = [
                'api.v1.auth.login',
                'api.v1.auth.logout',
                'api.v1.auth.me',
                'api.v1.auth.change-password',
            ];

            if (! in_array($request->route()?->getName(), $allowed, true)) {
                return ApiResponse::message(
                    'Anda harus mengganti kata sandi terlebih dahulu.',
                    403,
                    null,
                    'must_change_password',
                );
            }
        }

        return $next($request);
    }
}
