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

        // 2. Provision accounts and assign classes via StudentImportService (protects F1 accounts)
        try {
            $provisioned = $service->provisionStudents($students);
            $stats = $provisioned['stats'];
            $csvPath = $provisioned['csv_path'];
        } catch (\Throwable $e) {
            Log::error('[StudentImport] DB failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data ke database: ' . $e->getMessage());
        }

        $total = $stats['created'] + $stats['updated'];
        $message = "Berhasil mengimpor {$total} siswa";
        if ($stats['created'] > 0) $message .= " ({$stats['created']} baru)";
        if ($stats['updated'] > 0) $message .= " ({$stats['updated']} diperbarui)";
        if (!empty($stats['skipped_protected'])) $message .= " (" . count($stats['skipped_protected']) . " akun terlindung dilewati: " . implode(', ', $stats['skipped_protected']) . ")";
        if ($stats['classes_created'] > 0) $message .= ". {$stats['classes_created']} kelas baru dibuat otomatis.";
        if ($csvPath) $message .= " Kredensial sementara diekspor ke storage/app/private/{$csvPath}.";

        return back()->with('success', $message);
    }
}
