<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\AiDocumentParserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\AcademicCalendar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiImporterController extends Controller
{

    public function process(Request $request, AiDocumentParserService $aiService)
    {
        $request->validate([
            'document_type' => 'required|string|in:Daftar Siswa,Jadwal Pelajaran,Jadwal Ruangan,Kalender Pendidikan',
            'document_file' => 'required|file|mimes:pdf|max:15360',
            'cycle_type'    => 'nullable|string|in:Putaran 1,Putaran 2',
            'ai_model'      => 'nullable|string|max:50',
        ]);

        @ini_set('max_execution_time', 600);
        @set_time_limit(600);

        try {
            $pdfPath = $request->file('document_file')->getRealPath();
            $type = $request->document_type;
            $aiModel = $request->input('ai_model');
            
            $data = $aiService->parsePdf($pdfPath, $type, $aiModel);
            
            if (empty($data)) {
                $msg = 'AI tidak menemukan data yang relevan di dokumen ini.';
                return $request->expectsJson()
                    ? response()->json(['message' => $msg], 422)
                    : back()->with('error', $msg);
            }

            // Apply cycle_type override if selected by user
            $cycleType = $request->cycle_type;
            if ($cycleType && in_array($type, ['Jadwal Pelajaran', 'Jadwal Ruangan'])) {
                foreach ($data as &$row) {
                    $row['cycle_type'] = $cycleType;
                }
            }

            $originUrl = url()->previous();
            $cacheKey = 'ai_import_' . auth()->id() . '_' . time();
            Cache::put($cacheKey, ['type' => $type, 'data' => $data, 'origin_url' => $originUrl, 'cycle_type' => $cycleType], now()->addMinutes(30));

            if ($request->expectsJson()) {
                return response()->json([
                    'redirect' => route('ai-importer.preview', ['key' => $cacheKey]),
                ]);
            }

            return view('admin.ai-importer.preview', compact('data', 'type', 'cacheKey', 'originUrl'));
            
        } catch (\Throwable $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }
    }

    public function preview(string $key): View|RedirectResponse
    {
        if (! str_starts_with($key, 'ai_import_' . auth()->id() . '_')) {
            abort(403, 'Akses pratinjau import ditolak.');
        }

        $cacheData = Cache::get($key);
        if (! $cacheData) {
            return redirect()->route('dashboard')->with('error', 'Sesi import telah kedaluwarsa. Silakan unggah ulang.');
        }

        $data = $cacheData['data'];
        $type = $cacheData['type'];
        $originUrl = $cacheData['origin_url'] ?? url('/');
        $cacheKey = $key;

        return view('admin.ai-importer.preview', compact('data', 'type', 'cacheKey', 'originUrl'));
    }

    public function store(Request $request)
    {
        $cacheKey = $request->input('cache_key');
        $cacheData = Cache::get($cacheKey);

        if (!$cacheData) {
            return redirect()->back()->with('error', 'Sesi import telah kedaluwarsa. Silakan upload ulang.');
        }

        $type = $cacheData['type'];
        $data = $cacheData['data'];
        $count = 0;

        DB::beginTransaction();
        try {
            switch ($type) {
                case 'Daftar Siswa':
                    foreach ($data as $row) {
                        $class = SchoolClass::resolveOrCreate($row['class_name']);
                        $existingStudent = User::where('username', $row['username'])->first();
                        if ($existingStudent) {
                            $existingStudent->update([
                                'fullname' => $row['fullname'],
                                'role_id'  => 4,
                                'class_id' => $class->id,
                            ]);
                        } else {
                            User::create([
                                'username'             => $row['username'],
                                'fullname'             => $row['fullname'],
                                'password'             => Hash::make(Str::random(12)),
                                'must_change_password' => true,
                                'role_id'              => 4,
                                'class_id'             => $class->id,
                            ]);
                        }
                        $count++;
                    }
                    break;

                case 'Jadwal Pelajaran':
                    foreach ($data as $row) {
                        $cycleType = self::normalizeCycle($row['cycle_type'] ?? 'Putaran 1');
                        $dayOfWeek = self::normalizeDay($row['day_of_week'] ?? 'Senin');
                        $startTime = self::normalizeTime($row['start_time'] ?? '07:15:00');
                        $endTime = self::normalizeTime($row['end_time'] ?? $startTime);

                        $class = SchoolClass::resolveOrCreate($row['class_name'], $cycleType);
                        $teacher = User::where('fullname', 'LIKE', "%{$row['teacher_name']}%")->where('role_id', 2)->first();
                        if (!$teacher) {
                            $teacher = User::firstOrCreate(
                                ['username' => 'guru_' . uniqid()],
                                [
                                    'fullname'             => $row['teacher_name'],
                                    'password'             => Hash::make(Str::random(24)),
                                    'must_change_password' => true,
                                    'role_id'              => 2
                                ]
                            );
                        }

                        // Infer period from start_time
                        $inferred = \App\Services\LessonHourService::inferPeriodsFromTime(
                            $startTime, 
                            $endTime, 
                            $dayOfWeek, 
                            $cycleType
                        );
                        $startPeriod = $row['start_period'] ?? ($inferred['start_period'] ?? 1);
                        $endPeriod = $row['end_period'] ?? ($inferred['end_period'] ?? $startPeriod);

                        // Lookup RoomSchedule based on class, day, period, cycle
                        $roomSchedule = \App\Models\RoomSchedule::where('class_id', $class->id)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('period', $startPeriod)
                            ->where('cycle_type', $cycleType)
                            ->first();

                        $roomId = $roomSchedule?->room_id;
                        
                        Schedule::updateOrCreate(
                            [
                                'class_id' => $class->id,
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $startTime,
                                'cycle_type' => $cycleType
                            ],
                            [
                                'teacher_id' => $teacher->id,
                                'room_id' => $roomId,
                                'subject' => $row['subject'],
                                'end_time' => $endTime,
                                'start_period' => $startPeriod,
                                'end_period' => $endPeriod
                            ]
                        );
                        $count++;
                    }
                    break;

                case 'Jadwal Ruangan':
                    foreach ($data as $row) {
                        $cycle = self::normalizeCycle($row['cycle_type'] ?? 'Putaran 1');
                        $day = self::normalizeDay($row['day_of_week'] ?? 'Senin');
                        $class = SchoolClass::resolveOrCreate($row['class_name'], $cycle);
                        $room = Room::resolveOrCreate($row['room_name']);
                        
                        $p1 = $row['start_period'] ?? $row['period'] ?? 1;
                        $p2 = $row['end_period'] ?? $row['period'] ?? $p1;
                        $startPeriod = min((int)$p1, (int)$p2);
                        $endPeriod = max((int)$p1, (int)$p2);

                        for ($p = $startPeriod; $p <= $endPeriod; $p++) {
                            $startTime = \App\Services\LessonHourService::getStartTime($p, $day, $cycle) ?? '07:00:00';
                            $endTime = \App\Services\LessonHourService::getEndTime($p, $day, $cycle) ?? '07:45:00';

                            RoomSchedule::updateOrCreate(
                                [
                                    'class_id' => $class->id,
                                    'day_of_week' => $day,
                                    'period' => $p,
                                    'cycle_type' => $cycle
                                ],
                                [
                                    'room_id' => $room->id,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                ]
                            );
                            $count++;
                        }
                    }

                    self::syncScheduleRooms();
                    break;

                case 'Kalender Pendidikan':
                    foreach ($data as $row) {
                        AcademicCalendar::updateOrCreate(
                            [
                                'title' => $row['title'],
                                'start_date' => $row['start_date']
                            ],
                            [
                                'end_date' => $row['end_date'],
                                'category' => \App\Http\Controllers\AcademicCalendarController::normalizeCategory($row['category'] ?? null),
                                'is_kbm_off' => $row['is_kbm_off']
                            ]
                        );
                        $count++;
                    }
                    break;
            }

            DB::commit();
            Cache::forget($cacheKey);

            $originUrl = $cacheData['origin_url'] ?? url('/');
            if ($type === 'Jadwal Ruangan' && !empty($data[0]['day_of_week'])) {
                $targetDay = $data[0]['day_of_week'];
                $targetCycle = $data[0]['cycle_type'] ?? 'Putaran 2';
                $originUrl = route('room-schedules.index', ['day' => $targetDay, 'cycle' => $targetCycle]);
            }
            return redirect($originUrl)->with('success', "Berhasil menyimpan {$count} slot jam ({$type}) ke database.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Gagal menyimpan data: " . $e->getMessage());
        }
    }

    public static function normalizeDay(string $day): string
    {
        $d = strtolower(trim($day));
        return match ($d) {
            'senin', 'monday', 'mon' => 'Senin',
            'selasa', 'tuesday', 'tue' => 'Selasa',
            'rabu', 'wednesday', 'wed' => 'Rabu',
            'kamis', 'thursday', 'thu' => 'Kamis',
            'jumat', 'friday', 'fri' => 'Jumat',
            'sabtu', 'saturday', 'sat' => 'Sabtu',
            default => 'Senin',
        };
    }

    public static function normalizeCycle(string $cycle): string
    {
        $c = strtolower(trim($cycle));
        if (str_contains($c, '2') || str_contains($c, 'putaran 2')) {
            return 'Putaran 2';
        }
        return 'Putaran 1';
    }

    public static function normalizeTime(?string $time): string
    {
        $t = trim((string)$time);
        if ($t === '') {
            return '07:00:00';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
            return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
        }
        return $t;
    }

    public static function syncScheduleRooms(): void
    {
        $schedules = Schedule::all();
        foreach ($schedules as $sched) {
            $period = $sched->start_period ?? 1;
            $roomSched = RoomSchedule::where('class_id', $sched->class_id)
                ->where('day_of_week', $sched->day_of_week)
                ->where('period', $period)
                ->where('cycle_type', $sched->cycle_type)
                ->first();
            if ($roomSched && $sched->room_id !== $roomSched->room_id) {
                $sched->update(['room_id' => $roomSched->room_id]);
            }
        }
    }
}
