<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AcademicCalendarController extends Controller
{
    public function index(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $today = Carbon::today()->toDateString();
        $todayEvent = AcademicCalendarService::isKbmSuspendedToday($today);

        // Year & Month for Calendar View (default to today)
        $year = (int) $request->query('year', Carbon::today()->year);
        $month = (int) $request->query('month', Carbon::today()->month);

        if ($month < 1) {
            $month = 12;
            $year--;
        } elseif ($month > 12) {
            $month = 1;
            $year++;
        }

        $currentMonth = Carbon::createFromDate($year, $month, 1);
        $prevMonthDate = $currentMonth->copy()->subMonth();
        $nextMonthDate = $currentMonth->copy()->addMonth();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Calculate grid range with Sunday-Saturday alignment
        $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 = Sunday
        $gridStart = $startOfMonth->copy()->subDays($startDayOfWeek);
        
        $endDayOfWeek = $endOfMonth->dayOfWeek;
        $trailingDays = (6 - $endDayOfWeek);
        $gridEnd = $endOfMonth->copy()->addDays($trailingDays);

        // Fetch all calendar events overlapping the visible grid range
        $gridEvents = AcademicCalendar::where('start_date', '<=', $gridEnd->toDateString())
            ->where('end_date', '>=', $gridStart->toDateString())
            ->get();

        // Map events by date YYYY-MM-DD
        $eventsByDate = [];
        foreach ($gridEvents as $event) {
            $eStart = Carbon::parse($event->start_date)->max($gridStart);
            $eEnd = Carbon::parse($event->end_date)->min($gridEnd);
            $period = \Carbon\CarbonPeriod::create($eStart, $eEnd);
            foreach ($period as $dt) {
                $eventsByDate[$dt->toDateString()][] = [
                    'id'          => $event->id,
                    'title'       => $event->title,
                    'category'    => $event->category,
                    'is_kbm_off'  => (bool) $event->is_kbm_off,
                    'description' => $event->description,
                    'start_date'  => $event->start_date->translatedFormat('d M Y'),
                    'end_date'    => $event->end_date->translatedFormat('d M Y'),
                ];
            }
        }

        // Build grid days array
        $calendarGrid = [];
        $dayCursor = $gridStart->copy();
        while ($dayCursor->lte($gridEnd)) {
            $dateStr = $dayCursor->toDateString();
            $dayEvents = $eventsByDate[$dateStr] ?? [];
            
            // Priority category: libur_nasional > libur_bersama > agenda_sekolah
            $category = null;
            $isKbmOff = false;
            foreach ($dayEvents as $ev) {
                if ($ev['is_kbm_off']) $isKbmOff = true;
                if ($ev['category'] === 'libur_nasional') {
                    $category = 'libur_nasional';
                    break;
                } elseif ($ev['category'] === 'libur_bersama') {
                    $category = $category ?: 'libur_bersama';
                } elseif ($ev['category'] === 'agenda_sekolah') {
                    $category = $category ?: 'agenda_sekolah';
                }
            }

            $calendarGrid[] = [
                'date'            => $dateStr,
                'formattedDate'   => $dayCursor->translatedFormat('l, d F Y'),
                'dayNumber'       => $dayCursor->day,
                'dayOfWeek'       => $dayCursor->dayOfWeek,
                'isCurrentMonth'  => $dayCursor->month === $month,
                'isToday'         => $dateStr === $today,
                'isSunday'        => $dayCursor->dayOfWeek === 0,
                'events'          => $dayEvents,
                'primaryCategory' => $category,
                'isKbmOff'        => $isKbmOff,
            ];

            $dayCursor->addDay();
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $currentMonthName = $monthNames[$month] . ' ' . $year;

        // Specific events occurring in this month
        $monthEvents = AcademicCalendar::where('start_date', '<=', $endOfMonth->toDateString())
            ->where('end_date', '>=', $startOfMonth->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        // Calculate monthly KBM statistics
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
                return $cursor->between($ev->start_date, $ev->end_date) && ($ev->is_kbm_off || $ev->category !== 'agenda_sekolah');
            });

            if ($isSun || $hasOffEvent) {
                $statsOffDays++;
            }
            $cursor->addDay();
        }

        $statsEffectiveDays = max(0, $statsTotalDays - $statsOffDays);

        // All calendar entries for table list
        $calendars = AcademicCalendar::orderBy('start_date', 'desc')->get();

        return view('academic-calendar.index', compact(
            'calendars',
            'today',
            'todayEvent',
            'year',
            'month',
            'currentMonthName',
            'prevMonthDate',
            'nextMonthDate',
            'calendarGrid',
            'monthNames',
            'monthEvents',
            'statsTotalDays',
            'statsSundays',
            'statsOffDays',
            'statsEffectiveDays'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'category'    => 'required|in:libur_nasional,libur_bersama,agenda_sekolah',
            'is_kbm_off'  => 'boolean',
            'description' => 'nullable|string|max:500',
        ], [
            'title.required'          => 'Nama agenda / libur wajib diisi.',
            'start_date.required'     => 'Tanggal mulai wajib diisi.',
            'end_date.required'       => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'category.required'       => 'Kategori agenda wajib dipilih.',
        ]);

        $validated['is_kbm_off'] = $request->has('is_kbm_off');

        AcademicCalendar::create($validated);

        return back()->with('success', 'Agenda / Hari Libur Kalender Akademik berhasil ditambahkan.');
    }

    public function destroy(AcademicCalendar $academicCalendar)
    {
        $academicCalendar->delete();
        return back()->with('success', 'Kalender Akademik berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = fgetcsv($handle, 1000, ","); // Skip header
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Expected CSV format: Title, Start Date (YYYY-MM-DD), End Date (YYYY-MM-DD), Category, Is KBM Off (1/0), Description
            if (count($data) >= 4) {
                AcademicCalendar::create([
                    'title' => $data[0],
                    'start_date' => $data[1],
                    'end_date' => $data[2],
                    'category' => $data[3],
                    'is_kbm_off' => $data[4] ?? 1,
                    'description' => $data[5] ?? null,
                ]);
            }
        }
        
        fclose($handle);

        return back()->with('success', 'Data Kalender berhasil diimpor dari CSV.');
    }
}
