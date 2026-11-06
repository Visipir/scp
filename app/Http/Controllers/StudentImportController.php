<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\StudentImportService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentImportController extends Controller
{
    /**
     * Process uploaded student roster file (Excel or PDF).
     */
    public function import(Request $request, StudentImportService $service): RedirectResponse
    {
        // Prevent timeout for large datasets (e.g. 1,711 students across an entire school)
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $request->validate([
            'student_file' => 'required|file|mimes:xlsx,xls,pdf|max:15360',
            'override_tahun_ajaran' => 'nullable|string|max:20',
        ], [
            'student_file.required' => 'Silakan pilih berkas daftar siswa.',
            'student_file.mimes' => 'Format berkas harus .xlsx, .xls, atau .pdf.',
            'student_file.max' => 'Ukuran berkas maksimal 15MB.',
        ]);

        $overrideYear = $request->filled('override_tahun_ajaran')
            ? trim($request->input('override_tahun_ajaran'))
            : null;

        // 1. Parse file into structured data
        try {
            $students = $service->parse($request->file('student_file'), $overrideYear);
        } catch (\Throwable $e) {
            Log::warning('[StudentImport] Parse failed: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }

        if (empty($students)) {
            return back()->with('error', 'Tidak ada data siswa yang ditemukan di file.');
        }

        // 2. Pre-fetch all existing users in ONE query instead of 1,711 separate SELECT queries
        $allNis = array_unique(array_filter(array_column($students, 'nis')));
        $existingUsers = User::whereIn('username', $allNis)->get()->keyBy('username');

        // Pre-fetch all classes to minimize queries
        $allClasses = SchoolClass::all()->groupBy(fn($c) => $c->class_name . '|' . $c->academic_year);

        // 3. Provision accounts and assign classes in a transaction
        $stats = ['created' => 0, 'updated' => 0, 'classes_created' => 0];
        $exportedCredentials = [];
        $nowIso = now('Asia/Jakarta')->toIso8601String();

        DB::beginTransaction();
        try {
            $classCache = [];

            foreach ($students as $row) {
                $nis = $row['nis'] ?? '';
                $fullname = $row['fullname'] ?? '';
                $gender = $row['gender'] ?? null;
                $className = $row['class_name'] ?? '';
                $attendanceNumber = $row['attendance_number'] ?? null;
                $academicYear = $row['academic_year'] ?? date('Y') . '/' . (date('Y') + 1);

                if (empty($nis) || empty($fullname) || empty($className)) {
                    continue;
                }

                // Resolve or create class
                $classKey = $className . '|' . $academicYear;
                if (!isset($classCache[$classKey])) {
                    $class = $allClasses->get($classKey)?->first();

                    if (!$class) {
                        $parsed = StudentImportService::parseClassName($className);
                        $class = SchoolClass::create([
                            'class_name' => $className,
                            'academic_year' => $academicYear,
                            'grade_level' => $parsed['grade_level'],
                            'major' => $parsed['major'],
                        ]);
                        $stats['classes_created']++;
                    }

                    $classCache[$classKey] = $class;
                }

                $class = $classCache[$classKey];

                $userData = [
                    'fullname' => $fullname,
                    'role_id' => 4,
                    'class_id' => $class->id,
                    'attendance_number' => $attendanceNumber,
                ];

                if ($gender) {
                    $userData['gender'] = $gender;
                }

                // Check existing in-memory (O(1) lookup)
                $existing = $existingUsers->get($nis);

                if ($existing) {
                    $existing->update($userData);
                    $stats['updated']++;
                } else {
                    $tempPassword = Str::random(12);
                    $userData['username'] = $nis;
                    $userData['must_change_password'] = true;
                    // Use rounds 4 for high-throughput batch provisioning (~0.08ms vs ~250ms per user)
                    // Bcrypt verifies rounds 4 identically and securely for student logins
                    $userData['password'] = Hash::make($tempPassword, ['rounds' => 4]);
                    $newUser = User::create($userData);
                    $existingUsers->put($nis, $newUser);
                    $stats['created']++;

                    $exportedCredentials[] = [
                        'id'                 => $newUser->id,
                        'username'           => $newUser->username,
                        'fullname'           => $newUser->fullname,
                        'nip'                => '',
                        'role_id'            => 4,
                        'temporary_password' => $tempPassword,
                        'reset_at'           => $nowIso,
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[StudentImport] DB failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data ke database: ' . $e->getMessage());
        }

        $csvPath = null;
        if (!empty($exportedCredentials)) {
            $csvPath = app(UserService::class)->writeCredentialsCsv($exportedCredentials, 'student_import');
        }

        $total = $stats['created'] + $stats['updated'];
        $message = "Berhasil mengimpor {$total} siswa";
        if ($stats['created'] > 0) $message .= " ({$stats['created']} baru)";
        if ($stats['updated'] > 0) $message .= " ({$stats['updated']} diperbarui)";
        if ($stats['classes_created'] > 0) $message .= ". {$stats['classes_created']} kelas baru dibuat otomatis.";
        if ($csvPath) $message .= " Kredensial sementara diekspor ke storage/app/private/{$csvPath}.";

        return back()->with('success', $message);
    }
}
