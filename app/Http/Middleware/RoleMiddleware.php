<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        $intRoles = array_map('intval', $roles);
        $allowed = in_array((int) $user->role_id, $intRoles, true);

        // Allow teachers with homeroom or counselor assignments when route allows role 3 (Kesiswaan)
        if (!$allowed && in_array(3, $intRoles, true) && method_exists($user, 'hasKesiswaanAccess') && $user->hasKesiswaanAccess()) {
            $allowed = true;
        }

        if (!$allowed) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
