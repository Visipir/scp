<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\IneffectiveDay;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;

class AiChatbotTools
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ExamService $examService,
    ) {}

    /**
     * Gemini Function Declarations Schema.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeclarations(): array
    {
        return [
            [
                'name' => 'get_attendance_summary',
                'description' => 'Mendapatkan ringkasan presensi kehadiran siswa (Hadir, Sakit, Izin, Alpa, Terlambat, serta total tidak berangkat) dan daftar tanggal ketidakhadiran. Hanya dapat diakses oleh Siswa atau Orang Tua.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'period' => [
                            'type' => 'STRING',
                            'description' => 'Periode waktu presensi: this_month (bulan ini), last_month (bulan lalu), all_time (seluruh waktu), atau format YYYY-MM (misal 2026-11). Default: this_month.',
                        ],
                        'child' => [
                            'type' => 'STRING',
                            'description' => 'Nama anak jika pengguna adalah orang tua yang memiliki beberapa anak terdaftar.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_schedule',
                'description' => 'Mendapatkan jadwal pelajaran KBM sekolah. Siswa/Ortu melihat jadwal kelasnya, Guru melihat jadwal mengajarnya, Waka Kurikulum dapat melihat jadwal kelas atau guru tertentu. Memuat status siklus aktif dan informasi hari libur/KBM ditangguhkan.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'day' => [
                            'type' => 'STRING',
                            'description' => 'Hari jadwal: today (hari ini), tomorrow (besok), senin, selasa, rabu, kamis, jumat, sabtu. Default: today.',
                        ],
                        'cycle' => [
                            'type' => 'STRING',
                            'description' => 'Siklus putaran: active (siklus yang sedang aktif di sekolah), 1 (Putaran 1), atau 2 (Putaran 2). Default: active.',
                        ],
                        'class' => [
                            'type' => 'STRING',
                            'description' => 'Nama kelas (misal: "X PPLG 1", "XI BR 2") untuk Waka Kurikulum atau Guru BK.',
                        ],
                        'teacher' => [
                            'type' => 'STRING',
                            'description' => 'Nama guru pengajar untuk Waka Kurikulum.',
                        ],
                        'child' => [
                            'type' => 'STRING',
                            'description' => 'Nama anak untuk Orang Tua jika memiliki lebih dari 1 anak.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_my_journal_status',
                'description' => 'Mendapatkan status pengisian jurnal harian mengajar KBM bagi Guru pengajar pada tanggal tertentu, termasuk peringatan keterlambatan (late alert). Hanya dapat diakses oleh Guru.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'date' => [
                            'type' => 'STRING',
                            'description' => 'Tanggal pengisian jurnal: today (hari ini) atau format YYYY-MM-DD. Default: today.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_leave_requests',
                'description' => 'Mendapatkan daftar pengajuan surat permohonan izin/sakit siswa sesuai hak akses (Siswa/Ortu melihat miliknya, Wali Kelas/BK melihat kelas binaannya, Waka melihat seluruh sekolah). Maksimal 10 data.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'status' => [
                            'type' => 'STRING',
                            'description' => 'Filter status persetujuan surat: Pending, Approved, atau Rejected. Opsional.',
                        ],
                        'limit' => [
                            'type' => 'INTEGER',
                            'description' => 'Jumlah maksimal data permohonan izin yang diambil (maksimal 10). Default: 10.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_exams',
                'description' => 'Mendapatkan daftar materi pelajaran, bank soal, atau jadwal ujian sekolah sesuai hak akses pengguna. Maksimal 10 data.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'category' => [
                            'type' => 'STRING',
                            'description' => 'Kategori data: materi, bank_soal, atau ujian. Opsional.',
                        ],
                        'limit' => [
                            'type' => 'INTEGER',
                            'description' => 'Jumlah maksimal data yang diambil (maksimal 10). Default: 10.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_class_attendance_summary',
                'description' => 'Mendapatkan rekapitulasi statistik presensi satu rombongan belajar/kelas per bulan serta daftar nama siswa yang memiliki Alpa. Hanya untuk Waka Kurikulum dan Tim Kesiswaan/Wali Kelas/BK.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'month' => [
                            'type' => 'STRING',
                            'description' => 'Bulan rekapitulasi format YYYY-MM (misal: 2026-11). Default: bulan berjalan.',
                        ],
                        'class' => [
                            'type' => 'STRING',
                            'description' => 'Nama kelas (misal: "X PPLG 1", "XI BR 2"). Wajib diisi untuk Waka Kurikulum dan Guru BK; untuk Wali Kelas otomatis dibatasi pada kelas binaannya.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_unfilled_journals_today',
                'description' => 'Mendapatkan daftar seluruh jadwal KBM hari ini di sekolah yang belum diisi jurnalnya oleh guru pengajar. Hanya dapat diakses oleh Waka Kurikulum.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'get_system_info',
                'description' => 'Mendapatkan informasi konfigurasi sistem sekolah (siklus putaran aktif, status kunci jadwal, tanggal dan jam saat ini Asia/Jakarta, peran dan kelas pengguna aktif).',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => new \stdClass(),
                ],
            ],
        ];
    }

    /**
     * Dispatch and execute tool call based on name and validated arguments.
     *
     * @param User $user Authenticated actor
     * @param string $name Tool name
     * @param array<string, mixed> $args Function call arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, string $name, array $args): array
    {
        return match ($name) {
            'get_attendance_summary'       => $this->getAttendanceSummary($user, $args),
            'get_schedule'                 => $this->getSchedule($user, $args),
            'get_my_journal_status'        => $this->getMyJournalStatus($user, $args),
            'get_leave_requests'           => $this->getLeaveRequests($user, $args),
            'get_exams'                    => $this->getExams($user, $args),
            'get_class_attendance_summary' => $this->getClassAttendanceSummary($user, $args),
            'get_unfilled_journals_today'  => $this->getUnfilledJournalsToday($user, $args),
            'get_system_info'              => $this->getSystemInfo($user, $args),
            default                        => ['error' => "Alat '{$name}' tidak dikenali."],
        };
    }

    /**
     * 1. Tool get_attendance_summary
     */
    public function getAttendanceSummary(User $user, array $args): array
    {
        if ((int) $user->role_id !== 4) {
            return ['error' => 'tidak diizinkan'];
        }

        $children = $user->children()->where('role_id', 4)->orderBy('fullname')->get();
        $isParent = $children->isNotEmpty();

        $targetStudents = [];

        if ($isParent) {
            $childQuery = isset($args['child']) ? trim((string) $args['child']) : '';
            if ($childQuery !== '') {
                $matched = $children->filter(function ($c) use ($childQuery) {
                    return stripos($c->fullname, $childQuery) !== false;
                });

                if ($matched->isEmpty()) {
                    return ['error' => "Anak dengan nama '{$childQuery}' tidak ditemukan dalam daftar anak Anda."];
                }

                if ($matched->count() > 1) {
                    return [
                        'error' => "Nama anak '{$childQuery}' ambigu. Ditemukan beberapa anak: " . $matched->pluck('fullname')->join(', ') . '. Sebutkan nama yang lebih spesifik.',
                    ];
                }

                $targetStudents = [$matched->first()];
            } else {
                $targetStudents = $children->all();
            }
        } else {
            $targetStudents = [$user];
        }

        // Validate and resolve period
        $rawPeriod = isset($args['period']) ? trim((string) $args['period']) : 'this_month';
        $now = Carbon::now('Asia/Jakarta');

        if ($rawPeriod === 'this_month') {
            $periodLabel = 'Bulan ' . $now->translatedFormat('F Y');
            $yearMonth = $now->format('Y-m');
        } elseif ($rawPeriod === 'last_month') {
            $last = $now->copy()->subMonth();
            $periodLabel = 'Bulan ' . $last->translatedFormat('F Y');
            $yearMonth = $last->format('Y-m');
        } elseif ($rawPeriod === 'all_time') {
            $periodLabel = 'Semua Waktu';
            $yearMonth = null;
        } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawPeriod)) {
            $parsed = Carbon::createFromFormat('Y-m', $rawPeriod);
            $periodLabel = 'Bulan ' . $parsed->translatedFormat('F Y');
            $yearMonth = $rawPeriod;
        } else {
            return ['error' => 'Format periode tidak valid. Gunakan this_month, last_month, all_time, atau format YYYY-MM (misal: 2026-11).'];
        }

        $summaries = [];
        foreach ($targetStudents as $student) {
            $baseQuery = Attendance::where('student_id', $student->id);
            if ($yearMonth !== null) {
                [$y, $m] = array_map('intval', explode('-', $yearMonth));
                $baseQuery->whereYear('date', $y)->whereMonth('date', $m);
            }

            $countsRaw = (clone $baseQuery)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $counts = [
                'Hadir'     => (int) ($countsRaw['Hadir'] ?? 0),
                'Sakit'     => (int) ($countsRaw['Sakit'] ?? 0),
                'Izin'      => (int) ($countsRaw['Izin'] ?? 0),
                'Alpa'      => (int) ($countsRaw['Alpa'] ?? 0),
                'Terlambat' => (int) ($countsRaw['Terlambat'] ?? 0),
            ];

            $tidakBerangkat = $counts['Sakit'] + $counts['Izin'] + $counts['Alpa'];
            $terlambat = $counts['Terlambat'];

            $nonHadirRecords = (clone $baseQuery)
                ->whereIn('status', ['Sakit', 'Izin', 'Alpa', 'Terlambat'])
                ->orderByDesc('date')
                ->limit(31)
                ->get(['date', 'status']);

            $recordsMapped = [];
            $seq = 1;
            foreach ($nonHadirRecords as $rec) {
                $recordsMapped[] = [
                    'no'     => $seq++,
                    'date'   => (string) $rec->date,
                    'status' => (string) $rec->status,
                ];
            }

            $summaries[] = [
                'nama'                  => $student->fullname,
                'periode'               => $periodLabel,
                'ringkasan'             => $counts,
                'tidak_berangkat'       => $tidakBerangkat,
                'terlambat'             => $terlambat,
                'daftar_ketidakhadiran' => $recordsMapped,
            ];
        }

        return count($summaries) === 1 ? $summaries[0] : ['anak' => $summaries];
    }

    /**
     * 2. Tool get_schedule
     */
    public function getSchedule(User $user, array $args): array
    {
        $config = SystemConfiguration::first();
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        // Normalize cycle
        $rawCycle = isset($args['cycle']) ? trim((string) $args['cycle']) : 'active';
        if ($rawCycle === '1' || $rawCycle === 'Putaran 1') {
            $targetCycle = 'Putaran 1';
        } elseif ($rawCycle === '2' || $rawCycle === 'Putaran 2') {
            $targetCycle = 'Putaran 2';
        } else {
            $targetCycle = $activeCycle;
        }

        // Normalize day
        $rawDay = isset($args['day']) ? strtolower(trim((string) $args['day'])) : 'today';
        $now = Carbon::now('Asia/Jakarta');
        $dateForCheck = null;

        $dayMap = [
            'senin'   => 'Senin',
            'selasa'  => 'Selasa',
            'rabu'    => 'Rabu',
            'kamis'   => 'Kamis',
            'jumat'   => 'Jumat',
            'sabtu'   => 'Sabtu',
            'minggu'  => 'Minggu',
        ];

        if ($rawDay === 'today' || $rawDay === 'hari_ini') {
            $dayName = $this->dashboardService->indonesianDay($now);
            $dateForCheck = $now->toDateString();
        } elseif ($rawDay === 'tomorrow' || $rawDay === 'besok') {
            $tomorrow = $now->copy()->addDay();
            $dayName = $this->dashboardService->indonesianDay($tomorrow);
            $dateForCheck = $tomorrow->toDateString();
        } elseif (isset($dayMap[$rawDay])) {
            $dayName = $dayMap[$rawDay];
        } else {
            return ['error' => 'Pilihan hari tidak valid. Gunakan today, tomorrow, senin, selasa, rabu, kamis, jumat, atau sabtu.'];
        }

        if ($dayName === 'Minggu' || $dayName === null) {
            return [
                'day'             => 'Minggu',
                'total_schedules' => 0,
                'schedules'       => [],
                'info'            => 'Hari Minggu libur sekolah, tidak ada jadwal KBM.',
            ];
        }

        // Check ineffective day / calendar suspended event
        $isKbmOff = false;
        $offReason = null;
        if ($dateForCheck !== null) {
            $ineffective = IneffectiveDay::whereDate('date', $dateForCheck)->first();
            if (!$ineffective) {
                $calendarEvent = AcademicCalendarService::isKbmSuspendedToday($dateForCheck);
                if ($calendarEvent) {
                    $ineffective = (object) [
                        'description' => $calendarEvent->title,
                    ];
                }
            }

            if ($ineffective) {
                $isKbmOff = true;
                $offReason = 'Hari Libur / KBM Ditangguhkan: ' . $ineffective->description;
            }
        }

        $query = Schedule::with(['schoolClass', 'teacher', 'room'])
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $targetCycle);

        $roleId = (int) $user->role_id;

        if ($roleId === 4) {
            // Siswa or Ortu
            $children = $user->children()->where('role_id', 4)->get();
            if ($children->isNotEmpty()) {
                // Parent
                $childQuery = isset($args['child']) ? trim((string) $args['child']) : '';
                if ($childQuery !== '') {
                    $matched = $children->filter(fn($c) => stripos($c->fullname, $childQuery) !== false);
                    if ($matched->isEmpty()) {
                        return ['error' => "Anak dengan nama '{$childQuery}' tidak ditemukan."];
                    }
                    $classId = $matched->first()->class_id;
                } else {
                    $classId = $children->first()->class_id;
                }
            } else {
                // Student self
                $classId = $user->class_id;
            }

            if (!$classId) {
                return ['error' => 'Data kelas belum terdaftar pada akun ini.'];
            }

            $query->where('class_id', $classId);
        } elseif ($roleId === 2) {
            // Guru: own schedule
            $query->where('teacher_id', $user->id);
        } elseif ($roleId === 1) {
            // Waka: can filter by teacher or class
            if (!empty($args['teacher'])) {
                $teacherName = trim((string) $args['teacher']);
                $teacher = User::where('role_id', 2)
                    ->where('fullname', 'LIKE', "%{$teacherName}%")
                    ->first();
                if ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                } else {
                    return ['error' => "Guru dengan nama '{$teacherName}' tidak ditemukan."];
                }
            }

            if (!empty($args['class'])) {
                $className = trim((string) $args['class']);
                $classObj = SchoolClass::where('class_name', 'LIKE', "%{$className}%")->first();
                if ($classObj) {
                    $query->where('class_id', $classObj->id);
                } else {
                    return ['error' => "Kelas '{$className}' tidak ditemukan."];
                }
            }
        } elseif ($roleId === 3) {
            // Tim Kesiswaan / Wali Kelas / BK
            $scopedClassId = $user->scopedClassId();
            if ($scopedClassId !== null) {
                $query->where('class_id', $scopedClassId);
            } elseif (!empty($args['class'])) {
                $className = trim((string) $args['class']);
                $classObj = SchoolClass::where('class_name', 'LIKE', "%{$className}%")->first();
                if ($classObj) {
                    $query->where('class_id', $classObj->id);
                }
            }
        }

        $schedules = $query->orderBy('start_time')->limit(31)->get();

        // Check journal filled status for Guru
        $filledScheduleIds = [];
        if ($roleId === 2 && $dateForCheck !== null) {
            $filledScheduleIds = Journal::whereDate('date', $dateForCheck)
                ->whereIn('schedule_id', $schedules->pluck('id'))
                ->pluck('schedule_id')
                ->toArray();
        }

        $mappedSchedules = [];
        $seq = 1;
        foreach ($schedules as $sched) {
            $item = [
                'no'      => $seq++,
                'time'    => substr((string) $sched->start_time, 0, 5) . ' - ' . substr((string) $sched->end_time, 0, 5),
                'subject' => $sched->subject,
                'class'   => $sched->schoolClass?->class_name ?? '-',
                'room'    => $sched->room?->room_name ?? '-',
            ];

            if ($roleId !== 2) {
                $item['teacher'] = $sched->teacher?->fullname ?? '-';
            }

            if ($roleId === 2) {
                $item['journal_filled'] = in_array($sched->id, $filledScheduleIds, true);
            }

            $mappedSchedules[] = $item;
        }

        return [
            'active_cycle'    => $activeCycle,
            'viewed_cycle'    => $targetCycle,
            'day'             => $dayName,
            'date'            => $dateForCheck,
            'is_kbm_off'      => $isKbmOff,
            'kbm_off_reason'  => $offReason,
            'total_schedules' => count($mappedSchedules),
            'schedules'       => $mappedSchedules,
        ];
    }

    /**
     * 3. Tool get_my_journal_status
     */
    public function getMyJournalStatus(User $user, array $args): array
    {
        if ((int) $user->role_id !== 2) {
            return ['error' => 'tidak diizinkan'];
        }

        $rawDate = isset($args['date']) ? trim((string) $args['date']) : 'today';
        $now = Carbon::now('Asia/Jakarta');

        if ($rawDate === 'today' || $rawDate === 'hari_ini') {
            $targetDate = $now->toDateString();
        } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $rawDate)) {
            $targetDate = $rawDate;
        } else {
            return ['error' => 'Format tanggal tidak valid. Gunakan "today" atau format YYYY-MM-DD.'];
        }

        // Check if KBM suspended on target date
        $calendarEvent = AcademicCalendarService::isKbmSuspendedToday($targetDate);
        if ($calendarEvent) {
            return [
                'date'              => $targetDate,
                'is_kbm_suspended'  => true,
                'reason'            => 'KBM ditangguhkan: ' . $calendarEvent->title,
                'total_schedules'   => 0,
                'filled_count'      => 0,
                'unfilled_count'    => 0,
                'late_alerts_count' => 0,
                'schedules'         => [],
            ];
        }

        $parsedDate = Carbon::parse($targetDate);
        $dayName = $this->dashboardService->indonesianDay($parsedDate);
        if ($dayName === 'Minggu' || $dayName === null) {
            return [
                'date'              => $targetDate,
                'message'           => 'Hari Minggu libur KBM, tidak ada jurnal yang perlu diisi.',
                'total_schedules'   => 0,
                'filled_count'      => 0,
                'unfilled_count'    => 0,
                'late_alerts_count' => 0,
                'schedules'         => [],
            ];
        }

        $config = SystemConfiguration::first();
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $activeCycle)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [
                'date'              => $targetDate,
                'message'           => "Tidak ada jadwal mengajar pada hari {$dayName} ({$activeCycle}).",
                'total_schedules'   => 0,
                'filled_count'      => 0,
                'unfilled_count'    => 0,
                'late_alerts_count' => 0,
                'schedules'         => [],
            ];
        }

        $journals = Journal::whereDate('date', $targetDate)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->get()
            ->keyBy('schedule_id');

        // Check late alert schedules if date is today
        $lateAlertScheduleIds = [];
        if ($targetDate === $now->toDateString()) {
            $alerts = $this->dashboardService->unfilledJournalAlerts((int) $user->id, $config);
            $lateAlertScheduleIds = collect($alerts)->pluck('id')->all();
        }

        $mapped = [];
        $seq = 1;
        $filledCount = 0;
        $unfilledCount = 0;
        $lateCount = 0;

        foreach ($schedules as $sched) {
            $isFilled = $journals->has($sched->id);
            $isLate = in_array($sched->id, $lateAlertScheduleIds, true);

            if ($isFilled) {
                $filledCount++;
            } else {
                $unfilledCount++;
            }

            if ($isLate) {
                $lateCount++;
            }

            $journalRecord = $journals->get($sched->id);
            $topicSafe = null;
            if ($journalRecord && !empty($journalRecord->topic_description)) {
                $sanitized = htmlspecialchars(strip_tags((string) $journalRecord->topic_description), ENT_QUOTES, 'UTF-8');
                $topicSafe = "[DATA: topik_jurnal] {$sanitized} [/DATA]";
            }

            $mapped[] = [
                'no'             => $seq++,
                'time'           => substr((string) $sched->start_time, 0, 5) . ' - ' . substr((string) $sched->end_time, 0, 5),
                'class'          => $sched->schoolClass?->class_name ?? '-',
                'subject'        => $sched->subject,
                'journal_filled' => $isFilled,
                'is_late_alert'  => $isLate,
                'topic'          => $topicSafe,
            ];
        }

        return [
            'date'              => $targetDate,
            'active_cycle'      => $activeCycle,
            'total_schedules'   => count($mapped),
            'filled_count'      => $filledCount,
            'unfilled_count'    => $unfilledCount,
            'late_alerts_count' => $lateCount,
            'schedules'         => $mapped,
        ];
    }

    /**
     * 4. Tool get_leave_requests
     */
    public function getLeaveRequests(User $user, array $args): array
    {
        $roleId = (int) $user->role_id;
        if (!in_array($roleId, [1, 3, 4], true)) {
            return ['error' => 'tidak diizinkan'];
        }

        $limit = isset($args['limit']) ? min(max((int) $args['limit'], 1), 10) : 10;

        $query = LeaveRequest::visibleTo($user)->with(['student.schoolClass'])->orderByDesc('created_at');

        if (!empty($args['status'])) {
            $statusInput = strtolower(trim((string) $args['status']));
            $statusMap = [
                'pending'  => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];

            if (isset($statusMap[$statusInput])) {
                $query->where('status_approval', $statusMap[$statusInput]);
            }
        }

        $requests = $query->limit($limit)->get();

        $mapped = [];
        $seq = 1;
        foreach ($requests as $req) {
            $cleanReason = htmlspecialchars(strip_tags((string) $req->reason), ENT_QUOTES, 'UTF-8');
            $mapped[] = [
                'no'           => $seq++,
                'student_name' => $req->student?->fullname ?? 'Siswa',
                'class'        => $req->student?->schoolClass?->class_name ?? '-',
                'start_date'   => (string) $req->start_date,
                'end_date'     => (string) $req->end_date,
                'status'       => (string) $req->status_approval,
                'reason'       => "[DATA: alasan_izin] {$cleanReason} [/DATA]",
            ];
        }

        return [
            'total'          => count($mapped),
            'leave_requests' => $mapped,
        ];
    }

    /**
     * 5. Tool get_exams
     */
    public function getExams(User $user, array $args): array
    {
        $limit = isset($args['limit']) ? min(max((int) $args['limit'], 1), 10) : 10;
        $category = null;

        if (!empty($args['category'])) {
            $cat = strtolower(trim((string) $args['category']));
            if (in_array($cat, ['materi', 'bank_soal', 'ujian'], true)) {
                $category = $cat;
            }
        }

        $filters = array_filter(['category' => $category]);
        $exams = $this->examService->listExams($user, $filters, $limit);

        $mapped = [];
        $seq = 1;
        foreach ($exams as $exam) {
            $descSafe = null;
            if (!empty($exam->description)) {
                $cleanDesc = htmlspecialchars(strip_tags((string) $exam->description), ENT_QUOTES, 'UTF-8');
                $descSafe = "[DATA: deskripsi] {$cleanDesc} [/DATA]";
            }

            $mapped[] = [
                'no'          => $seq++,
                'title'       => $exam->title,
                'category'    => $exam->category,
                'subject'     => $exam->subject,
                'class'       => $exam->schoolClass?->class_name ?? 'Semua',
                'exam_date'   => $exam->exam_date ? (string) $exam->exam_date : null,
                'time'        => ($exam->start_time && $exam->end_time) ? substr((string) $exam->start_time, 0, 5) . ' - ' . substr((string) $exam->end_time, 0, 5) : null,
                'description' => $descSafe,
            ];
        }

        return [
            'total' => count($mapped),
            'exams' => $mapped,
        ];
    }

    /**
     * 6. Tool get_class_attendance_summary
     */
    public function getClassAttendanceSummary(User $user, array $args): array
    {
        $roleId = (int) $user->role_id;
        if (!in_array($roleId, [1, 3], true)) {
            return ['error' => 'tidak diizinkan'];
        }

        $targetClass = null;
        $scopedClassId = $user->scopedClassId();

        if ($roleId === 3 && $scopedClassId !== null) {
            // Wali Kelas is strictly restricted to their homeroom class
            $targetClass = SchoolClass::find($scopedClassId);
            if (!$targetClass) {
                return ['error' => 'Kelas binaan Wali Kelas tidak ditemukan.'];
            }
        } else {
            // Role 1 or Guru BK
            $classQuery = isset($args['class']) ? trim((string) $args['class']) : '';
            if ($classQuery === '') {
                return ['error' => 'Nama kelas harus disertakan (misal: "X PPLG 1", "XI BR 2").'];
            }

            $targetClass = SchoolClass::where('class_name', 'LIKE', "%{$classQuery}%")->first();
            if (!$targetClass) {
                return ['error' => "Kelas '{$classQuery}' tidak ditemukan dalam sistem."];
            }
        }

        // Validate and resolve month
        $monthInput = isset($args['month']) ? trim((string) $args['month']) : Carbon::now('Asia/Jakarta')->format('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthInput)) {
            return ['error' => 'Format bulan tidak valid. Gunakan format YYYY-MM (misal: 2026-11).'];
        }

        [$year, $monthNumber] = array_map('intval', explode('-', $monthInput));
        $students = User::where('class_id', $targetClass->id)
            ->where('role_id', 4)
            ->orderBy('fullname')
            ->get(['id', 'fullname']);

        $studentIds = $students->pluck('id');

        $base = Attendance::whereIn('student_id', $studentIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $monthNumber);

        $countsRaw = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'Hadir'     => (int) ($countsRaw['Hadir'] ?? 0),
            'Sakit'     => (int) ($countsRaw['Sakit'] ?? 0),
            'Izin'      => (int) ($countsRaw['Izin'] ?? 0),
            'Alpa'      => (int) ($countsRaw['Alpa'] ?? 0),
            'Terlambat' => (int) ($countsRaw['Terlambat'] ?? 0),
        ];

        // List students with Alpa (name only, max 20)
        $alpaRecords = (clone $base)
            ->where('status', 'Alpa')
            ->orderBy('date')
            ->get(['student_id', 'date']);

        $alpaByStudent = [];
        foreach ($alpaRecords as $rec) {
            $alpaByStudent[$rec->student_id][] = (string) $rec->date;
        }

        $alpaStudentsList = [];
        $seq = 1;
        foreach ($students as $st) {
            if (isset($alpaByStudent[$st->id])) {
                $alpaStudentsList[] = [
                    'no'         => $seq++,
                    'nama'       => $st->fullname,
                    'total_alpa' => count($alpaByStudent[$st->id]),
                    'tanggal'    => array_values(array_unique($alpaByStudent[$st->id])),
                ];
                if (count($alpaStudentsList) >= 20) {
                    break;
                }
            }
        }

        $parsedMonth = Carbon::createFromDate($year, $monthNumber, 1);

        return [
            'kelas'             => $targetClass->class_name,
            'bulan'             => 'Bulan ' . $parsedMonth->translatedFormat('F Y'),
            'total_siswa'       => $students->count(),
            'ringkasan'         => $counts,
            'total_alpa'        => $counts['Alpa'],
            'daftar_siswa_alpa' => $alpaStudentsList,
        ];
    }

    /**
     * 7. Tool get_unfilled_journals_today
     */
    public function getUnfilledJournalsToday(User $user, array $args): array
    {
        if ((int) $user->role_id !== 1) {
            return ['error' => 'tidak diizinkan'];
        }

        $config = SystemConfiguration::first();
        $unfilled = $this->dashboardService->wakaUnfilledSchedules($config);

        $mapped = [];
        $seq = 1;
        foreach ($unfilled->slice(0, 31) as $sched) {
            $mapped[] = [
                'no'      => $seq++,
                'guru'    => $sched->teacher?->fullname ?? '-',
                'mapel'   => $sched->subject,
                'kelas'   => $sched->schoolClass?->class_name ?? '-',
                'jam'     => substr((string) $sched->start_time, 0, 5) . ' - ' . substr((string) $sched->end_time, 0, 5),
            ];
        }

        $now = Carbon::now('Asia/Jakarta');

        return [
            'tanggal'                => $now->translatedFormat('l, d F Y'),
            'total_belum_mengisi'    => $unfilled->count(),
            'daftar_jurnal_tertunda' => $mapped,
        ];
    }

    /**
     * 8. Tool get_system_info
     */
    public function getSystemInfo(User $user, array $args): array
    {
        $config = SystemConfiguration::first();
        $now = Carbon::now('Asia/Jakarta');

        $friendlyRole = match ((int) $user->role_id) {
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => $user->scopedClassId() !== null ? 'Wali Kelas' : 'Guru BK',
            4 => $user->children()->exists() ? 'Orang Tua' : 'Siswa',
            default => 'Pengguna Sekolah',
        };

        $userClass = null;
        if ((int) $user->role_id === 4 && $user->class_id) {
            $userClass = $user->schoolClass?->class_name;
        } elseif ((int) $user->role_id === 3 && $user->scopedClassId() !== null) {
            $userClass = $user->schoolClass?->class_name;
        }

        $childrenNames = [];
        if ((int) $user->role_id === 4 && $user->children()->exists()) {
            $childrenNames = $user->children()->pluck('fullname')->all();
        }

        return [
            'siklus_aktif'     => $config?->active_cycle ?? 'Putaran 1',
            'jadwal_terkunci'  => (bool) ($config?->is_schedule_locked ?? false),
            'waktu_sistem'     => $now->translatedFormat('l, d F Y H:i:s') . ' WIB',
            'tanggal'          => $now->toDateString(),
            'peran_pengguna'   => $friendlyRole,
            'kelas_pengguna'   => $userClass,
            'daftar_anak'      => $childrenNames,
        ];
    }
}
