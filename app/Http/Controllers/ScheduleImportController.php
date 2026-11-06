<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\GeminiScheduleParserService;
use App\Services\LessonHourService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScheduleImportController extends Controller
{
    public const TEACHER_CODE_MAP = [
        '2' => 'Aris Purwantinah, S.Pd.',
        '3' => 'Sunarsih, S.Pd.',
        '4' => 'Sri Sunartini, S.Pd.',
        '6' => 'Amat Sukardi, S.Pd.',
        '7' => 'Muhammad Heru Purnomo, M.S.I',
        '8' => 'Dra. Eko Mei Wulan',
        '9' => 'Sujar Hartono, S.Pd.',
        '10' => 'Dra. Yatimatin Nafiah, MM.',
        '11' => 'Wahyuni, S.Pd.',
        '12' => 'Windu Mahmud, M.Eng.',
        '13' => 'Tri Susilowati, S.T., M.Pd.',
        '14' => 'Tatik Wardayati, S.Pd.',
        '15' => 'Drs. Susanto Dwi Prihantoro, M.Pd.',
        '16' => 'Raden Rara Bernadeta Sri Widyastuti, S.Pd.',
        '17' => 'Ririh Damayanti Wisnu Giyono, M.Acc.',
        '18' => 'Yuli Astuti, S.Pd.',
        '19' => 'Theresia Indri Wahyuni, S.Pd.',
        '20' => 'Danurejo, S.Pd.',
        '21' => 'Dra. Mukirah, S.Pd.',
        '22' => 'Widodo, S.Pd.',
        '23' => 'Yuli Arbiatiningsih, S.Pd.',
        '24' => 'Margiyati, S.Pd.',
        '25' => 'Umi Kuntari, S.S.',
        '26' => 'Wakhid Bashori, S.Pd.',
        '27' => 'Agnes Endang Mudji Rahayu, M.Pd.',
        '28' => 'Dian Awaliati, S.Pd.',
        '29' => 'Retnaningsih, S.Pd.',
        '30' => 'Ristiyanti, S.Pd.',
        '31' => 'Isti Fitriana, S.Pd.',
        '32' => 'Nurzanah, S.Pd.',
        '33' => 'Dwi Lesmiyati, S.Pd.',
        '34' => 'Anastya Ratna Sulistiyani, S.Pd.',
        '35' => 'Agatha Christiyanti Manullang, S.S.',
        '36' => 'Sri Widayati, S.Pd., M.Pd.',
        '37' => 'Budiyantoro, S.Pd.',
        '38' => 'Tantri Agustiana, S.Pd.',
        '39' => 'Failla Rochmayanti, M.Pd.',
        '40' => 'Mulati, S.Pd.',
        '41' => 'Lauhin Ilmi Agustin, S.Pd.',
        '42' => 'Dra. Siti Khusnul Khotimah',
        '43' => 'Murdiasih Kadarwati, S.Pd.',
        '44' => 'Diah Utaminingsih, S.T.',
        '45' => 'Raden Rara Uningsuciasih, S.Pd.',
        '46' => 'Andriyani, S.Kom.',
        '47' => 'Dariyati, S.Kom.',
        '48' => 'Sutarmi, S.Pd.',
        '49' => 'Sulardi, S.Pd.',
        '50' => 'Rosyida Ramadhani, S.Pd.',
        '51' => 'Tentrem Yuniati Lestari, S.Pd.',
        '52' => 'Yanu Iswantara, S.Pd.',
        '53' => 'Sri Sunarsih, S.Pd.',
        '54' => 'Kurnia Safitri, S.Pd.',
        '55' => 'Rini Bhintarti, S.Pd.',
        '56' => 'Tatik Herawati, S.Pd.',
        '57' => 'Rusdiyanto, S.Pd.',
        '58' => 'Ahmad Muhlasin, S.Pd.',
        '59' => 'Suratimah, S.Pd.I',
        '60' => 'Sri Nur Arafah, S.Pd.',
        '61' => 'Eva Yuni Astuti, S.Pd.',
        '62' => 'Hermi Cahyawati, S.Pd.I',
        '63' => 'Eneng Fitriani Puspitowati, S.Pd.Si.',
        '64' => 'Eridha Dewi Sepitasari, S.Pd.',
        '65' => 'Titisari, S.Kom.',
        '66' => 'Ajeng Fitri Saraswati, S.Pd.',
        '67' => 'Wati, S.Pd.',
        '68' => 'Haris Budiawan, S.Pd.',
        '69' => 'Rohmad Dwiyanto, S.Pd.',
        '70' => 'Putri Wulanjari, S.Pd.',
        '71' => 'Duwi Rianto, S.Pd.',
        '72' => 'Yulita Eka Winajeng, S.Pd.',
        '73' => 'Menik Lestari, S.Pd.',
        '74' => 'Ninda Aprilia, S.Pd.',
        '75' => 'Muhammad Furqon, S.Sos.',
        '76' => 'Novia Rahmalina, S.Pd.',
        '77' => 'Reny Murni Hidayati, S.Pd.',
        '78' => 'Junia Sari, S.Pd.',
        '79' => 'Risa Resti Afriani, S.Pd.',
        '80' => 'Fiani Nur Aji Pangastuti, S.Pd.',
        '81' => 'Dinda Ayu Dewanti, S.Kom.',
        '82' => 'Rahmawati Yoga Pamungkas, M.Pd.',
        '83' => 'Dita Rizki Dewinta Sari, S.Pd.',
        '84' => 'Sellyana Nurul Azizah, S.Pd.',
        '85' => 'Diana Rohmaniah, S.Kom.',
        '87' => 'Aisyah Wulandari, S.Pd.',
        '88' => 'Nurhidayat Sulistiawan, S.Pd.',
        '89' => 'Ryawidyaningtyas, S.Pd.',
        '90' => 'Candra Rahadian, S.Pd.',
        '91' => 'Alivi Sazkia Hawie, S.Pd.',
    ];

    /**
     * Handle PDF timetable upload, parse with Gemini AI, and synchronize to database.
     */
    public function import(Request $request, GeminiScheduleParserService $parser): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB
            'cycle_type'        => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
            'replace_existing'  => ['nullable', 'boolean'],
        ], [
            'schedule_pdf.required' => 'Silakan pilih berkas PDF jadwal FET untuk diunggah.',
            'schedule_pdf.mimes'    => 'Berkas harus berupa dokumen PDF (.pdf).',
            'schedule_pdf.max'      => 'Ukuran berkas PDF maksimal adalah 10 Megabyte (10MB).',
            'cycle_type.in'         => 'Pilihan putaran jadwal harus Putaran 1 atau Putaran 2.',
        ]);

        $targetCycle = $validated['cycle_type'] ?? 'Putaran 2';

        $config = \App\Models\SystemConfiguration::first();
        if ($config && (bool) $config->is_schedule_locked) {
            abort(422, 'Jadwal sedang dikunci oleh Waka Kurikulum.');
        }

        try {
            $parsedItems = $parser->parsePdf($request->file('schedule_pdf'), $targetCycle);
        } catch (\Throwable $e) {
            Log::error('Schedule Import Parsing Failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses jadwal dengan AI: ' . $e->getMessage());
        }

        if (empty($parsedItems)) {
            return back()->with('error', 'Google Gemini tidak menemukan jadwal yang valid pada berkas PDF tersebut.');
        }

        $importedCount = 0;
        $conflictCount = 0;
        $newClassesCount = 0;
        $newRoomsCount = 0;

        DB::beginTransaction();
        try {
            if ($request->boolean('replace_existing')) {
                Schedule::where('cycle_type', $targetCycle)->doesntHave('journals')->delete();
            }
            foreach ($parsedItems as $item) {
                $className = trim($item['class_name'] ?? '');
                $subject = trim($item['subject'] ?? 'Pelajaran');
                $dayOfWeek = trim($item['day_of_week'] ?? 'Senin');
                $roomName = trim($item['room_name'] ?? 'R. Teori');
                $teacherName = trim($item['teacher_name'] ?? '');
                $teacherCode = trim($item['teacher_code'] ?? '');
                $startTime = trim($item['start_time'] ?? '');
                $endTime = trim($item['end_time'] ?? '');
                $cycleType = $targetCycle;

                if (empty($className) || empty($startTime) || empty($endTime)) {
                    continue;
                }

                // Format time to H:i:s
                $startTime = strlen($startTime) === 5 ? "{$startTime}:00" : substr($startTime, 0, 8);
                $endTime = strlen($endTime) === 5 ? "{$endTime}:00" : substr($endTime, 0, 8);

                // 1. Resolve Class
                $class = SchoolClass::where('class_name', $className)->first();
                if (!$class) {
                    $class = SchoolClass::create([
                        'class_name'    => $className,
                        'academic_year' => '2026/2027',
                    ]);
                    $newClassesCount++;
                }

                // 2. Resolve Room (Normalize theory rooms to numbered rooms)
                if (preg_match('/^(?:teori|r\.?\s*teori|ruang)\s*(\d+)$/i', $roomName, $m)) {
                    $roomName = 'Ruang ' . $m[1];
                } elseif (in_array(strtolower($roomName), ['teori', 'ruang teori', 'r. teori', 'ruang kbm', 'kbm'])) {
                    $roomName = 'Ruang 1';
                }

                $room = Room::where('room_name', $roomName)->first();
                if (!$room) {
                    $room = Room::create([
                        'room_name' => $roomName ?: 'Ruang 1',
                    ]);
                    $newRoomsCount++;
                }

                // 3. Resolve Teacher (Guru role_id = 2)
                $teacher = null;
                if (!empty($teacherCode)) {
                    // Check predefined official teacher code map first
                    if (isset(self::TEACHER_CODE_MAP[$teacherCode])) {
                        $mappedName = self::TEACHER_CODE_MAP[$teacherCode];
                        $teacher = User::where('role_id', 2)
                            ->where('fullname', $mappedName)
                            ->first();
                    }

                    if (!$teacher) {
                        $teacher = User::where('role_id', 2)
                            ->where(function ($q) use ($teacherCode) {
                                $q->where('username', 'like', "%{$teacherCode}%")
                                  ->orWhere('fullname', 'like', "%({$teacherCode})%")
                                  ->orWhere('fullname', 'like', "{$teacherCode} -%");
                            })
                            ->first();
                    }
                }

                if (!$teacher && !empty($teacherName)) {
                    $teacher = User::where('role_id', 2)
                        ->where(function ($q) use ($teacherName) {
                            $q->where('fullname', 'like', "%{$teacherName}%")
                              ->orWhere('username', 'like', '%' . Str::slug($teacherName, '_') . '%');
                        })
                        ->first();
                }

                if (!$teacher) {
                    // Fallback: create teacher record
                    $identifier = $teacherCode ?: ($teacherName ?: Str::random(5));
                    $slug = Str::slug($identifier, '_');
                    $teacher = User::firstOrCreate(
                        ['username' => "guru_{$slug}"],
                        [
                            'fullname'             => $teacherName ?: "Guru {$teacherCode}",
                            'password'             => bcrypt(Str::random(24)),
                            'must_change_password' => true,
                            'role_id'              => 2,
                        ]
                    );
                }

                // 4. Resolve Periods from time
                $inferred = LessonHourService::inferPeriodsFromTime($startTime, $endTime, $dayOfWeek, $cycleType);
                $startPeriod = $item['period'] ?? ($inferred['start_period'] ?? 1);
                $endPeriod = $inferred['end_period'] ?? $startPeriod;

                // 5. Conflict Prevention Check
                $conflict = Schedule::where('day_of_week', $dayOfWeek)
                    ->where('cycle_type', $cycleType)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->where(function ($q) use ($teacher, $room, $class) {
                        $q->where('teacher_id', $teacher->id)
                          ->orWhere('room_id', $room->id)
                          ->orWhere('class_id', $class->id);
                    })
                    ->exists();

                if ($conflict) {
                    $conflictCount++;
                    continue;
                }

                // 6. Insert Schedule
                Schedule::create([
                    'teacher_id'   => $teacher->id,
                    'class_id'     => $class->id,
                    'room_id'      => $room->id,
                    'subject'      => $subject,
                    'day_of_week'  => $dayOfWeek,
                    'start_period' => $startPeriod,
                    'end_period'   => $endPeriod,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'cycle_type'   => $cycleType,
                ]);

                $importedCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Database transaction error during schedule import: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menyimpan jadwal ke basis data: ' . $e->getMessage());
        }

        $msg = "AI Berhasil mengekstrak dan mengimpor {$importedCount} jadwal pelajaran untuk {$targetCycle}.";
        if ($conflictCount > 0) {
            $msg .= " ({$conflictCount} jadwal dilewati karena bentrok waktu).";
        }
        if ($newClassesCount > 0 || $newRoomsCount > 0) {
            $msg .= " Menambahkan {$newClassesCount} kelas baru dan {$newRoomsCount} ruangan baru.";
        }

        return redirect()->route('schedules.index', ['cycle' => $targetCycle])
            ->with('success', $msg);
    }

    public function index(): \Illuminate\View\View
    {
        $config = \App\Models\SystemConfiguration::first();
        return view('schedules.import-index', compact('config'));
    }

    public function preview(Request $request, \App\Services\FetScheduleParserService $parser): \Illuminate\View\View|RedirectResponse
    {
        $request->validate([
            'schedule_file' => ['required', 'file', 'max:20480'],
            'cycle_type'    => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
        ]);

        $cycleType = $request->input('cycle_type', 'Putaran 2');

        try {
            $preview = $parser->generatePreview($request->file('schedule_file'), $cycleType);
            return view('schedules.import-preview', compact('preview'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function commit(Request $request, \App\Services\FetScheduleParserService $parser): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $applied = $parser->commit($validated['token']);
            return redirect()->route('schedules.index', ['cycle' => 'Putaran 2'])->with(
                'success',
                sprintf(
                    'Import Jadwal FET selesai: %d jadwal berhasil disimpan, %d jadwal dengan jurnal lama dipertahankan.',
                    $applied['inserted'],
                    $applied['preserved']
                )
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menerapkan jadwal: ' . $e->getMessage());
        }
    }
}
