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

        // All calendar entries for table list (paginated if > 15)
        $calendars = AcademicCalendar::orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString();

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

    public function update(Request $request, AcademicCalendar $academicCalendar)
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

        $academicCalendar->update($validated);

        return back()->with('success', 'Agenda Kalender Akademik berhasil diperbarui.');
    }

    public function destroy(AcademicCalendar $academicCalendar)
    {
        $academicCalendar->delete();
        return back()->with('success', 'Kalender Akademik berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $filePath = $request->file('file')->getRealPath();
        $handle = @fopen($filePath, "r");
        if (!$handle) {
            return back()->with('error', 'Gagal membuka berkas CSV yang diunggah.');
        }

        // Auto-detect delimiter (, or ;)
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = ',';
        if ($firstLine !== false && substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        }

        $header = fgetcsv($handle, 2000, $delimiter);
        $importedCount = 0;

        $parseDate = function(?string $raw): ?string {
            $val = trim((string)$raw);
            if ($val === '') return null;
            // Check YYYY-MM-DD
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $val, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }
            // Check DD/MM/YYYY or DD-MM-YYYY
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            }
            $ts = strtotime($val);
            return $ts ? date('Y-m-d', $ts) : null;
        };

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
                // Skip completely empty lines
                if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                if (count($data) >= 3) {
                    $title = trim($data[0] ?? '');
                    $startDate = $parseDate($data[1] ?? null);
                    $endDate = $parseDate($data[2] ?? null) ?: $startDate;
                    $category = self::normalizeCategory($data[3] ?? null);

                    $rawOff = strtolower(trim((string)($data[4] ?? '1')));
                    $isKbmOff = in_array($rawOff, ['1', 'true', 'ya', 'yes', 'libur'], true) ? 1 : 0;
                    $description = isset($data[5]) ? trim($data[5]) : null;

                    if ($title !== '' && $startDate !== null) {
                        AcademicCalendar::create([
                            'title'       => $title,
                            'start_date'  => $startDate,
                            'end_date'    => $endDate,
                            'category'    => $category,
                            'is_kbm_off'  => $isKbmOff,
                            'description' => $description,
                        ]);
                        $importedCount++;
                    }
                }
            }
            fclose($handle);
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            fclose($handle);
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Academic Calendar Import Failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengimpor kalender akademik: ' . $e->getMessage());
        }

        if ($importedCount === 0) {
            return back()->with('error', 'Tidak ada data kegiatan kalender yang valid ditemukan di dalam berkas CSV.');
        }

        return back()->with('success', "Berhasil mengimpor {$importedCount} agenda kegiatan ke Kalender Akademik.");
    }

    public static function normalizeCategory(?string $category): string
    {
        $c = strtolower(trim((string)$category));
        if (str_contains($c, 'libur_nasional') || str_contains($c, 'nasional')) {
            return 'libur_nasional';
        }
        if (str_contains($c, 'libur_bersama') || str_contains($c, 'bersama')) {
            return 'libur_bersama';
        }
        if (str_contains($c, 'libur')) {
            return 'libur_nasional';
        }
        return 'agenda_sekolah';
    }
}
