<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolClassService
{
    /**
     * List classes with student counts, filters, and centralized authorizedClassIds scoping.
     */
    public function listClasses(User $actor, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        // Paginated class list requires class access (Waka, BK, Wali Kelas, or teaching teacher)
        if ($perPage !== null && !$actor->hasClassAccess()) {
            abort(403, 'Akses ditolak.');
        }

        $query = SchoolClass::withCount('users as students_count')
            ->with(['homeroomAssignment.user', 'counselorAssignments.user']);

        // Centralized scoping: Waka (null) sees all; BK, Wali Kelas & teaching teachers see union of authorizedClassIds
        if ($perPage !== null || $actor->hasKesiswaanAccess() || !empty($filters['scoped'])) {
            $allowedClassIds = $actor->authorizedClassIds();
            if ($allowedClassIds !== null) {
                $query->whereIn('id', $allowedClassIds);
            }
        }

        if (!empty($filters['class_id'])) {
            $query->where('id', (int) $filters['class_id']);
        }

        if (!empty($filters['academic_year']) && $filters['academic_year'] !== 'all') {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (!empty($filters['grade_level']) && $filters['grade_level'] !== 'all') {
            $query->where('grade_level', $filters['grade_level']);
        }

        if (!empty($filters['major']) && $filters['major'] !== 'all') {
            $query->where('major', $filters['major']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('class_name', 'like', "%{$search}%");
        }

        $query->orderBy('class_name', 'asc');

        if ($perPage !== null) {
            $perPage = min(max($perPage, 1), 50);
            return $query->paginate($perPage)->withQueryString();
        }

        return $query->get();
    }

    /**
     * Store new class with auto-parsed grade level and major.
     */
    public function createClass(User $actor, array $data): SchoolClass
    {
        $className = trim($data['class_name']);
        $academicYear = trim($data['academic_year']);

        $parsed = StudentImportService::parseClassName($className);

        return SchoolClass::create([
            'class_name'    => $className,
            'academic_year' => $academicYear,
            'grade_level'   => $parsed['grade_level'] ?? null,
            'major'         => $parsed['major'] ?? null,
        ]);
    }

    /**
     * Retrieve class detail, students, and attendance analytics with homeroom scoping.
     */
    public function getClassDetail(
        User $actor,
        SchoolClass $class,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $search = null
    ): array {
        $actor->authorizeClassAccess($class);

        $class->load(['homeroomAssignment.user', 'counselorAssignments.user']);
        $teachers = ((int) $actor->role_id === 1) ? User::where('role_id', 2)->orderBy('fullname')->get() : collect();

        $studentsQuery = $class->students()
            ->orderByRaw('attendance_number IS NULL, attendance_number ASC')
            ->orderBy('fullname', 'asc');

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id');

        $attendanceQuery = Attendance::whereIn('student_id', $studentIds);

        if ($startDate) {
            $attendanceQuery->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $attendanceQuery->whereDate('date', '<=', $endDate);
        }

        $attendances = $attendanceQuery->with(['student', 'journal.schedule'])->get();
        $groupedAttendances = $attendances->groupBy('student_id');

        $totalHadir = $attendances->where('status', 'Hadir')->count();
        $totalSakit = $attendances->where('status', 'Sakit')->count();
        $totalIzin = $attendances->where('status', 'Izin')->count();
        $totalAlpa = $attendances->where('status', 'Alpa')->count();
        $totalTerlambat = $attendances->where('status', 'Terlambat')->count();
        $totalRecords = $attendances->count();

        $attendanceRate = $totalRecords > 0
            ? round(($totalHadir / $totalRecords) * 100, 1)
            : 0;

        $recentAbsences = $attendances->whereIn('status', ['Sakit', 'Izin', 'Alpa', 'Terlambat'])
            ->sortByDesc('date')
            ->take(25)
            ->values();

        return [
            'class'              => $class,
            'students'           => $students,
            'groupedAttendances' => $groupedAttendances,
            'totalHadir'         => $totalHadir,
            'totalSakit'         => $totalSakit,
            'totalIzin'          => $totalIzin,
            'totalAlpa'          => $totalAlpa,
            'totalTerlambat'     => $totalTerlambat,
            'totalRecords'       => $totalRecords,
            'attendanceRate'     => $attendanceRate,
            'summary'            => [
                'total_hadir'     => $totalHadir,
                'total_sakit'     => $totalSakit,
                'total_izin'      => $totalIzin,
                'total_alpa'      => $totalAlpa,
                'total_terlambat' => $totalTerlambat,
                'total_records'   => $totalRecords,
                'attendance_rate' => $attendanceRate,
            ],
            'recentAbsences'     => $recentAbsences,
            'recent_absences'    => $recentAbsences,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'search'             => $search,
            'teachers'           => $teachers,
        ];
    }

    /**
     * Update class attributes.
     */
    public function updateClass(User $actor, SchoolClass $class, array $data): SchoolClass
    {
        $actor->authorizeClassSupervision($class);

        $className = trim($data['class_name']);
        $academicYear = trim($data['academic_year']);

        $parsed = StudentImportService::parseClassName($className);

        $class->update([
            'class_name'    => $className,
            'academic_year' => $academicYear,
            'grade_level'   => $parsed['grade_level'] ?? $class->grade_level,
            'major'         => $parsed['major'] ?? $class->major,
        ]);

        return $class->fresh();
    }

    /**
     * Check whether class can be deleted without violating constraints.
     */
    public function checkDeletable(SchoolClass $class): ?string
    {
        if ($class->users()->exists()) {
            return "Tidak dapat menghapus kelas {$class->class_name} karena masih memiliki data siswa terdaftar.";
        }

        if ($class->schedules()->exists()) {
            return "Tidak dapat menghapus kelas {$class->class_name} karena terdapat jadwal mengajar yang tertaut.";
        }

        return null;
    }

    /**
     * Delete class with dependency constraints.
     */
    public function deleteClass(User $actor, SchoolClass $class): void
    {
        $actor->authorizeClassSupervision($class);

        if ($err = $this->checkDeletable($class)) {
            abort(422, $err);
        }

        $class->delete();
    }

    /**
     * Get monthly or daily attendance recap for a class (JSON).
     */
    public function getMonthlyRecap(
        User $actor,
        SchoolClass $class,
        string $month,
        ?string $customStartDate = null,
        ?string $customEndDate = null
    ): array {
        $actor->authorizeClassAccess($class);

        if ($customStartDate !== null && $customEndDate !== null) {
            $startDate = $customStartDate;
            $endDate = $customEndDate;
        } else {
            $dt = Carbon::createFromFormat('Y-m', $month) ?: Carbon::now();
            $startDate = $dt->copy()->startOfMonth()->toDateString();
            $endDate = $dt->copy()->endOfMonth()->toDateString();
        }

        $students = $class->students()
            ->orderByRaw('attendance_number IS NULL, attendance_number ASC')
            ->orderBy('fullname', 'asc')
            ->get();

        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $studentRecaps = [];
        $classHadir = 0;
        $classSakit = 0;
        $classIzin = 0;
        $classAlpa = 0;
        $classTerlambat = 0;

        foreach ($students as $student) {
            $records = $attendances->get($student->id, collect());
            $h = $records->where('status', 'Hadir')->count();
            $s = $records->where('status', 'Sakit')->count();
            $i = $records->where('status', 'Izin')->count();
            $a = $records->where('status', 'Alpa')->count();
            $t = $records->where('status', 'Terlambat')->count();
            $total = $records->count();
            $rate = $total > 0 ? round(($h / $total) * 100, 1) : 0;

            $classHadir += $h;
            $classSakit += $s;
            $classIzin += $i;
            $classAlpa += $a;
            $classTerlambat += $t;

            $studentRecaps[] = [
                'id'                => (int) $student->id,
                'attendance_number' => $student->attendance_number !== null ? (int) $student->attendance_number : null,
                'fullname'          => $student->fullname,
                'username'          => $student->username,
                'gender'            => $student->gender,
                'hadir'             => $h,
                'sakit'             => $s,
                'izin'              => $i,
                'alpa'              => $a,
                'terlambat'         => $t,
                'total'             => $total,
                'attendance_rate'   => $rate,
            ];
        }

        $totalRecords = $classHadir + $classSakit + $classIzin + $classAlpa + $classTerlambat;
        $classRate = $totalRecords > 0 ? round(($classHadir / $totalRecords) * 100, 1) : 0;

        return [
            'class' => [
                'id'            => (int) $class->id,
                'class_name'    => $class->class_name,
                'academic_year' => $class->academic_year,
            ],
            'month'    => $month,
            'summary'  => [
                'total_hadir'     => $classHadir,
                'total_sakit'     => $classSakit,
                'total_izin'      => $classIzin,
                'total_alpa'      => $classAlpa,
                'total_terlambat' => $classTerlambat,
                'total_records'   => $totalRecords,
                'attendance_rate' => $classRate,
            ],
            'students' => $studentRecaps,
        ];
    }

    /**
     * Export attendance CSV stream matching the web controller exactly.
     */
    public function exportAttendanceStream(User $actor, SchoolClass $class, string $startDate, string $endDate): StreamedResponse
    {
        $actor->authorizeClassAccess($class);

        $students = $class->students()->orderBy('fullname', 'asc')->get();

        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Rekap_Presensi_{$class->class_name}_{$startDate}_sd_{$endDate}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $columns = ['No', 'Nama Siswa', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Terlambat'];

        $callback = function () use ($students, $attendances, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $no = 1;
            foreach ($students as $student) {
                $studentAttendances = $attendances->get($student->id, collect());

                $row = [
                    $no++,
                    $student->fullname,
                    $studentAttendances->where('status', 'Hadir')->count(),
                    $studentAttendances->where('status', 'Izin')->count(),
                    $studentAttendances->where('status', 'Sakit')->count(),
                    $studentAttendances->where('status', 'Alpa')->count(),
                    $studentAttendances->where('status', 'Terlambat')->count(),
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export student login cards CSV per class (NIS, Nama Siswa, Kelas, Password Sementara).
     * Strictly restricted to Waka (role_id = 1) and the Wali Kelas of that class.
     * Reuses storage/app/private/credentials/student_credentials_latest.csv when available.
     */
    public function exportLoginCardsStream(User $actor, SchoolClass $class): StreamedResponse
    {
        abort_unless($actor->canExportClassLoginCards($class), 403, 'Akses ditolak. Hanya Waka Kurikulum dan Wali Kelas kelas ini yang dapat mengunduh kartu login.');

        $students = $class->students()
            ->orderByRaw('attendance_number IS NULL, attendance_number ASC')
            ->orderBy('fullname', 'asc')
            ->get();

        // Reuse latest exported student credentials if available
        $cachedPasswords = [];
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if ($disk->exists('credentials/student_credentials_latest.csv')) {
            $raw = $disk->get('credentials/student_credentials_latest.csv');
            $lines = preg_split('/\r\n|\r|\n/', trim((string) $raw));
            if (count($lines) > 1) {
                $header = str_getcsv(array_shift($lines));
                $usernameIdx = array_search('username', $header, true);
                $passIdx = array_search('temporary_password', $header, true);
                if ($usernameIdx !== false && $passIdx !== false) {
                    foreach ($lines as $line) {
                        if ($line === '') {
                            continue;
                        }
                        $cols = str_getcsv($line);
                        if (isset($cols[$usernameIdx], $cols[$passIdx]) && $cols[$passIdx] !== '') {
                            $cachedPasswords[$cols[$usernameIdx]] = $cols[$passIdx];
                        }
                    }
                }
            }
        }

        $newlyGenerated = [];
        $nowIso = now('Asia/Jakarta')->toIso8601String();
        $rows = [];

        foreach ($students as $student) {
            if ($student->isProtectedAccount() || !(bool) $student->must_change_password) {
                $tempPass = '(Sudah Diganti)';
            } elseif (isset($cachedPasswords[$student->username])) {
                $tempPass = $cachedPasswords[$student->username];
            } else {
                $tempPass = \Illuminate\Support\Str::random(12);
                $student->password = \Illuminate\Support\Facades\Hash::make($tempPass, ['rounds' => 4]);
                $student->must_change_password = true;
                $student->save();
                $student->tokens()->delete();

                $newlyGenerated[] = [
                    'id'                 => $student->id,
                    'username'           => $student->username,
                    'fullname'           => $student->fullname,
                    'nip'                => '',
                    'role_id'            => 4,
                    'temporary_password' => $tempPass,
                    'reset_at'           => $nowIso,
                ];
            }

            $rows[] = [
                $student->username,
                $student->fullname,
                $class->class_name,
                $tempPass,
            ];
        }

        if (!empty($newlyGenerated)) {
            $safeClass = preg_replace('/[^A-Za-z0-9_-]/', '_', $class->class_name);
            app(UserService::class)->writeCredentialsCsv($newlyGenerated, "login_cards_{$safeClass}");
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Kartu_Login_{$class->class_name}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $columns = ['NIS', 'Nama Siswa', 'Kelas', 'Password Sementara'];

        $callback = function () use ($rows, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
