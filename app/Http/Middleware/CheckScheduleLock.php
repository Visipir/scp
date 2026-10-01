<?php

namespace App\Http\Middleware;

use App\Models\SystemConfiguration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckScheduleLock
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = SystemConfiguration::first();

        if ($config && $config->is_schedule_locked && $request->user()?->role_id !== 1) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Jadwal sedang dikunci oleh Waka Kurikulum.'], 403);
            }

            return redirect()->route('schedules.index')
                ->with('error', 'Jadwal sedang dikunci. Tidak dapat menambah/mengubah/menghapus jadwal.');
        }

        return $next($request);
    }
}
