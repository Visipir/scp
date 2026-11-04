<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\LessonHourService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomScheduleController extends Controller
{
    /**
     * Display the room timetable matrix.
     */
    public function index(Request $request): View
    {
        $config = SystemConfiguration::first();
        
        $dayFilter = $request->query('day', now()->format('l'));
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
        ];
        if (isset($dayMap[$dayFilter])) {
            $dayFilter = $dayMap[$dayFilter];
        }

        // Cycle filter
        $cycleFilter = $request->filled('cycle') ? $request->query('cycle') : ($config?->active_cycle ?? 'Putaran 1');

        // Fetch schedules
        $schedules = RoomSchedule::with(['schoolClass', 'teacher', 'room'])
            ->where('day_of_week', $dayFilter)
            ->where('cycle_type', $cycleFilter)
            ->get();

        $allClasses = SchoolClass::orderBy('class_name')->get();
        $classFilter = $request->query('class_id');
        $classes = $classFilter 
            ? $allClasses->where('id', $classFilter) 
            : $allClasses;

        $rooms = Room::orderBy('room_name')->get();
        $scheduleMatrix = LessonHourService::getAllMatrix();

        return view('room-schedules.index', compact(
            'rooms',
            'classes',
            'allClasses',
            'classFilter',
            'dayFilter', 
            'cycleFilter', 
            'config', 
            'scheduleMatrix',
            'schedules'
        ));
    }

    /**
     * Show the form for creating a new room schedule.
     */
    public function create()
    {
        $rooms = Room::orderBy('room_name')->get();
        $classes = SchoolClass::orderBy('class_name')->get();
        $teachers = User::where('role_id', 2)->orderBy('fullname')->get();
        
        return view('room-schedules.create', compact('rooms', 'classes', 'teachers'));
    }

    /**
     * Store a newly created room schedule in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'period' => 'required|integer|min:1|max:12',
            'cycle_type' => 'required|in:Putaran 1,Putaran 2',
        ]);

        // Conflict check
        $exists = RoomSchedule::where('room_id', $request->room_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('period', $request->period)
            ->where('cycle_type', $request->cycle_type)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['conflict' => 'Ruangan ini sudah digunakan pada hari, jam, dan putaran tersebut.']);
        }

        $startTime = LessonHourService::getStartTime($request->period, $request->day_of_week, $request->cycle_type);
        $endTime = LessonHourService::getEndTime($request->period, $request->day_of_week, $request->cycle_type);

        RoomSchedule::create([
            'room_id' => $request->room_id,
            'class_id' => $request->class_id,
            'teacher_id' => $request->teacher_id,
            'subject' => $request->subject,
            'day_of_week' => $request->day_of_week,
            'period' => $request->period,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'cycle_type' => $request->cycle_type,
        ]);

        return redirect()->route('room-schedules.index', [
            'day' => $request->day_of_week,
            'cycle' => $request->cycle_type
        ])->with('success', 'Jadwal ruangan berhasil ditambahkan.');
    }

    /**
     * Remove the specified room schedule from storage.
     */
    public function destroy(RoomSchedule $roomSchedule)
    {
        $roomSchedule->delete();
        return back()->with('success', 'Jadwal ruangan berhasil dihapus.');
    }

    /**
     * Extract schedule from PDF using Gemini API and populate room schedules.
     */
    public function import(Request $request, \App\Services\AiDocumentParserService $aiService)
    {
        // Support both schedule_pdf and pdf_file input names
        $fileKey = $request->hasFile('schedule_pdf') ? 'schedule_pdf' : 'pdf_file';

        $validated = $request->validate([
            $fileKey            => ['required', 'file', 'mimes:pdf', 'max:15360'], // 15MB
            'cycle_type'        => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
            'replace_existing'  => ['nullable', 'boolean'],
        ], [
            "{$fileKey}.required" => 'Silakan pilih berkas PDF jadwal FET untuk diunggah.',
            "{$fileKey}.mimes"    => 'Berkas harus berupa dokumen PDF (.pdf).',
            "{$fileKey}.max"      => 'Ukuran berkas PDF maksimal adalah 15 Megabyte (15MB).',
            'cycle_type.in'       => 'Pilihan putaran jadwal harus Putaran 1 atau Putaran 2.',
        ]);

        $targetCycle = $validated['cycle_type'] ?? 'Putaran 2';

        @ini_set('max_execution_time', 600);
        @set_time_limit(600);

        try {
            $parsedItems = $aiService->parsePdf($request->file($fileKey)->getRealPath(), 'Jadwal Ruangan');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Room Schedule Import Parsing Failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses jadwal dengan AI: ' . $e->getMessage());
        }

        if (empty($parsedItems)) {
            return back()->with('error', 'Google Gemini tidak menemukan jadwal yang valid pada berkas PDF tersebut.');
        }

        $importedCount = 0;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($request->boolean('replace_existing')) {
                RoomSchedule::where('cycle_type', $targetCycle)->delete();
            }

            foreach ($parsedItems as $item) {
                $className = trim($item['class_name'] ?? '');
                $roomName = trim($item['room_name'] ?? '');
                $dayOfWeek = trim($item['day_of_week'] ?? 'Senin');
                $cycleType = $item['cycle_type'] ?? $targetCycle;

                if (empty($className) || empty($roomName)) {
                    continue;
                }

                $class = SchoolClass::resolveOrCreate($className, $cycleType);
                $room = Room::resolveOrCreate($roomName);

                $p1 = $item['start_period'] ?? $item['period'] ?? 1;
                $p2 = $item['end_period'] ?? $item['period'] ?? $p1;
                $startPeriod = min((int)$p1, (int)$p2);
                $endPeriod = max((int)$p1, (int)$p2);

                for ($p = $startPeriod; $p <= $endPeriod; $p++) {
                    $pStartTime = LessonHourService::getStartTime($p, $dayOfWeek, $cycleType) ?? '07:00:00';
                    $pEndTime = LessonHourService::getEndTime($p, $dayOfWeek, $cycleType) ?? '07:45:00';

                    RoomSchedule::updateOrCreate(
                        [
                            'class_id'   => $class->id,
                            'day_of_week'=> $dayOfWeek,
                            'period'     => $p,
                            'cycle_type' => $cycleType,
                        ],
                        [
                            'room_id'    => $room->id,
                            'start_time' => $pStartTime,
                            'end_time'   => $pEndTime,
                        ]
                    );

                    $importedCount++;
                }
            }

            \App\Http\Controllers\AiImporterController::syncScheduleRooms();
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Database transaction error during room schedule import: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menyimpan jadwal ruangan ke basis data: ' . $e->getMessage());
        }

        $targetDay = !empty($parsedItems[0]['day_of_week']) ? $parsedItems[0]['day_of_week'] : 'Senin';
        return redirect()->route('room-schedules.index', [
            'cycle' => $targetCycle,
            'day'   => $targetDay,
        ])->with('success', "AI Berhasil mengekstrak dan mengimpor {$importedCount} slot jam jadwal ruangan ({$targetCycle} - {$targetDay}).");
    }
}
