<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * List calendar events with optional monthly statistics.
     */
    public function getCalendarOverview(?int $year = null, ?int $month = null): array
    {
        $year = $year ?: Carbon::today()->year;
        $month = $month ?: Carbon::today()->month;

        $currentMonth = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $monthEvents = AcademicCalendar::where('start_date', '<=', $endOfMonth->toDateString())
            ->where('end_date', '>=', $startOfMonth->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        $statsTotalDays = $currentMonth->daysInMonth;
        $statsSundays = 0;
        $statsOffDays = 0;

        $cursor = $startOfMonth->copy();
        while ($cursor->lte($endOfMonth)) {
            $isSun = ($cursor->dayOfWeek === 0);
            if ($isSun) {
                $statsSundays++;
            }

            $hasOffEvent = $monthEvents->contains(function ($ev) use ($cursor) {
                return $cursor->between($ev->start_date, $ev->end_date)
                    && ($ev->is_kbm_off || $ev->category !== 'agenda_sekolah');
            });

            if ($isSun || $hasOffEvent) {
                $statsOffDays++;
            }
            $cursor->addDay();
        }

        $statsEffectiveDays = max(0, $statsTotalDays - $statsOffDays);

        $todayStr = Carbon::today()->toDateString();
        $todayEvent = self::isKbmSuspendedToday($todayStr);

        return [
            'year'           => $year,
            'month'          => $month,
            'stats'          => [
                'total_days'     => $statsTotalDays,
                'sundays'        => $statsSundays,
                'off_days'       => $statsOffDays,
                'effective_days' => $statsEffectiveDays,
            ],
            'today_suspended' => $todayEvent !== null,
            'today_event'     => $todayEvent ? [
                'id'       => $todayEvent->id,
                'title'    => $todayEvent->title,
                'category' => $todayEvent->category,
            ] : null,
            'events'         => $monthEvents,
        ];
    }

    /**
     * List all calendar events without grid/stats.
     */
    public function listAllEvents(): Collection
    {
        return AcademicCalendar::orderBy('start_date', 'desc')->get();
    }

    /**
     * Store new calendar event.
     */
    public function createEvent(array $data): AcademicCalendar
    {
        return AcademicCalendar::create([
            'title'       => $data['title'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'category'    => $data['category'],
            'is_kbm_off'  => !empty($data['is_kbm_off']),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Delete calendar event.
     */
    public function deleteEvent(AcademicCalendar $calendar): void
    {
        $calendar->delete();
    }
}
