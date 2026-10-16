<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Services\AcademicCalendarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function index(): JsonResponse
    {
        $config = SystemConfiguration::first();

        return ApiResponse::data([
            'active_cycle'         => $config?->active_cycle ?? 'Putaran 1',
            'is_schedule_locked'   => (bool) ($config?->is_schedule_locked ?? false),
            'server_time'          => now()->toIso8601String(),
            'today_kbm_suspended'  => AcademicCalendarService::isKbmSuspendedToday(now()->toDateString()) !== null,
        ]);
    }
}
