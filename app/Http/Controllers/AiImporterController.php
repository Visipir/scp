<?php

namespace App\Http\Controllers;

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
                return back()->with('error', 'AI tidak menemukan data yang relevan di dokumen ini.');
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

            return view('admin.ai-importer.preview', compact('data', 'type', 'cacheKey', 'originUrl'));
            
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
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
                        $cycleType = $row['cycle_type'] ?? 'Putaran 1';
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
                            $row['start_time'], 
                            $row['end_time'] ?? $row['start_time'], 
                            $row['day_of_week'], 
                            $cycleType
                        );
                        $startPeriod = $row['start_period'] ?? ($inferred['start_period'] ?? 1);
                        $endPeriod = $row['end_period'] ?? ($inferred['end_period'] ?? $startPeriod);

                        // Lookup RoomSchedule based on class, day, period, cycle
                        $roomSchedule = \App\Models\RoomSchedule::where('class_id', $class->id)
                            ->where('day_of_week', $row['day_of_week'])
                            ->where('period', $startPeriod)
                            ->where('cycle_type', $cycleType)
                            ->first();

                        $roomId = $roomSchedule?->room_id;
                        
                        Schedule::updateOrCreate(
                            [
                                'class_id' => $class->id,
                                'day_of_week' => $row['day_of_week'],
                                'start_time' => $row['start_time'],
                                'cycle_type' => $cycleType
                            ],
                            [
                                'teacher_id' => $teacher->id,
                                'room_id' => $roomId,
                                'subject' => $row['subject'],
                                'end_time' => $row['end_time'],
                                'start_period' => $startPeriod,
                                'end_period' => $endPeriod
                            ]
                        );
                        $count++;
                    }
                    break;

                case 'Jadwal Ruangan':
                    foreach ($data as $row) {
                        $cycle = $row['cycle_type'] ?? 'Putaran 1';
                        $class = SchoolClass::resolveOrCreate($row['class_name'], $cycle);
                        $room = Room::resolveOrCreate($row['room_name']);
                        
                        $day = $row['day_of_week'];
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
                                'category' => $row['category'],
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
