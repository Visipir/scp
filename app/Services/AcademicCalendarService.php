<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use Carbon\Carbon;

class AcademicCalendarService
{
    /**
     * Checks if regular teaching classes are suspended today.
     * Returns the AcademicCalendar event if KBM is off, otherwise null.
     */
    public static function isKbmSuspendedToday($date = null): ?AcademicCalendar
    {
        $date = $date ? Carbon::parse($date)->toDateString() : today()->toDateString();

        return AcademicCalendar::where('is_kbm_off', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}
