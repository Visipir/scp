<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherEmailImportService
{
    public function __construct(
        private readonly EmployeeImportService $employeeService
    ) {}

    /**
     * Parse email list file (CSV or XLSX), match teachers, generate preview.
     */
    public function generatePreview(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        $rows = [];
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $spreadsheet = IOFactory::load($realPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, true);
            $rows = $this->extractFromSpreadsheet($rawRows);
        } else {
            $rows = $this->extractFromCsv($realPath);
        }

        if (empty($rows)) {
            throw new \RuntimeException('Tidak ada data email yang valid di dalam berkas.');
        }

        $existingTeachers = User::where('role_id', 2)->get();
        $matched = [];
        $unmatched = [];

        foreach ($rows as $row) {
            $teacher = $this->matchTeacher($row, $existingTeachers);
            if ($teacher) {
                $matched[] = [
                    'teacher_id'       => $teacher->id,
                    'teacher_username' => $teacher->username,
                    'teacher_fullname' => $teacher->fullname,
                    'nip'              => $row['nip'] ?: $teacher->nip,
                    'current_email'    => $teacher->email,
                    'new_email'        => strtolower(trim($row['email'])),
                ];
            } else {
                $unmatched[] = $row;
            }
        }

        $token = 'email_import_' . Str::random(32);
        $payload = [
            'token'     => $token,
            'summary'   => [
                'total_rows' => count($rows),
                'matched'    => count($matched),
                'unmatched'  => count($unmatched),
            ],
            'matched'   => $matched,
            'unmatched' => $unmatched,
        ];

        Cache::put($token, $payload, now()->addHours(2));

        return $payload;
    }

    /**
     * Commit email import in transaction.
     */
    public function commit(string $token): array
    {
        $payload = Cache::get($token);
        if (!$payload) {
            throw new \RuntimeException('Sesi preview import email sudah kedaluwarsa.');
        }

        $matched = $payload['matched'] ?? [];
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($matched as $item) {
                $user = User::find($item['teacher_id']);
                if ($user && !empty($item['new_email'])) {
                    $user->email = $item['new_email'];
                    $user->save();
                    $updatedCount++;
                }
            }

            DB::commit();
            Cache::forget($token);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['updated' => $updatedCount];
    }

    private function matchTeacher(array $row, Collection $teachers): ?User
    {
        // 1. Match by NIP
        if (!empty($row['nip'])) {
            $cleanNip = preg_replace('/[^\d]/', '', $row['nip']);
            $found = $teachers->first(fn($t) => !empty($t->nip) && preg_replace('/[^\d]/', '', $t->nip) === $cleanNip);
            if ($found) {
                return $found;
            }
        }

        // 2. Match by normalized name
        if (!empty($row['name'])) {
            $cleanName = $this->employeeService->normalizeName($row['name']);
            $found = $teachers->first(fn($t) => $this->employeeService->normalizeName($t->fullname) === $cleanName);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    private function extractFromSpreadsheet(array $rawRows): array
    {
        $headerMap = null;
        $rows = [];

        foreach ($rawRows as $r) {
            if ($headerMap === null) {
                $upper = array_map(fn($v) => strtoupper(trim((string) $v)), $r);
                $headerMap = [];
                foreach ($upper as $col => $name) {
                    if (str_contains($name, 'NIP')) $headerMap['nip'] = $col;
                    elseif (str_contains($name, 'NAMA') || str_contains($name, 'GURU')) $headerMap['name'] = $col;
                    elseif (str_contains($name, 'EMAIL') || str_contains($name, 'SUREL')) $headerMap['email'] = $col;
                }
                continue;
            }

            $email = trim((string) ($r[$headerMap['email'] ?? ''] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $rows[] = [
                'nip'   => trim((string) ($r[$headerMap['nip'] ?? ''] ?? '')),
                'name'  => trim((string) ($r[$headerMap['name'] ?? ''] ?? '')),
                'email' => $email,
            ];
        }

        return $rows;
    }

    private function extractFromCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $rows = [];
        $headerMap = null;

        while (($r = fgetcsv($handle, 1000, ',')) !== false) {
            if ($headerMap === null) {
                $upper = array_map(fn($v) => strtoupper(trim((string) $v)), $r);
                $headerMap = [];
                foreach ($upper as $idx => $name) {
                    if (str_contains($name, 'NIP')) $headerMap['nip'] = $idx;
                    elseif (str_contains($name, 'NAMA') || str_contains($name, 'GURU')) $headerMap['name'] = $idx;
                    elseif (str_contains($name, 'EMAIL') || str_contains($name, 'SUREL')) $headerMap['email'] = $idx;
                }
                continue;
            }

            $email = trim((string) ($r[$headerMap['email'] ?? 2] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $rows[] = [
                'nip'   => trim((string) ($r[$headerMap['nip'] ?? 0] ?? '')),
                'name'  => trim((string) ($r[$headerMap['name'] ?? 1] ?? '')),
                'email' => $email,
            ];
        }

        fclose($handle);
        return $rows;
    }
}
