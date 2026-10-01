<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     * Force users with must_change_password=true to change their password upon first login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            $allowedRoutes = [
                'password.first-change',
                'password.first-change.update',
                'logout',
            ];

            $currentRouteName = $request->route()?->getName();

            if (!in_array($currentRouteName, $allowedRoutes)) {
                return redirect()->route('password.first-change');
            }
        }

        return $next($request);
    }
}
