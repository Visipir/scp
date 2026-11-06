<?php

namespace App\Services;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class HomeroomImportService
{
    public const FUZZY_THRESHOLD = 85.0;

    public function __construct(
        private readonly EmployeeImportService $employeeService
    ) {}

    /**
     * Parse spreadsheet file, match rows against database classes & teachers, and generate preview without DB modifications.
     */
    public function generatePreview(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $rawRows = $sheet->toArray(null, true, true, true);
        $parsedRows = $this->extractDataRows($rawRows);

        $existingClasses = SchoolClass::all();
        $existingTeachers = User::where('role_id', 2)->get();

        $matchedRows = [];
        $summary = [
            'total_rows'        => count($parsedRows),
            'classes_matched'   => 0,
            'classes_unmatched' => 0,
            'wali_matched'      => 0,
            'wali_fuzzy'        => 0,
            'wali_unmatched'    => 0,
            'bk_matched'        => 0,
            'bk_fuzzy'          => 0,
            'bk_unmatched'      => 0,
        ];

        foreach ($parsedRows as $row) {
            $matchedClass = $this->matchClass($row['class_raw'], $existingClasses);
            if ($matchedClass) {
                $summary['classes_matched']++;
            } else {
                $summary['classes_unmatched']++;
            }

            $waliMatch = $this->matchTeacher($row['wali_raw'], $existingTeachers);
            if ($waliMatch['category'] === 'matched') {
                $summary['wali_matched']++;
            } elseif ($waliMatch['category'] === 'fuzzy') {
                $summary['wali_fuzzy']++;
            } else {
                $summary['wali_unmatched']++;
            }

            $bkMatch = $this->matchTeacher($row['bk_raw'], $existingTeachers);
            if ($bkMatch['category'] === 'matched') {
                $summary['bk_matched']++;
            } elseif ($bkMatch['category'] === 'fuzzy') {
                $summary['bk_fuzzy']++;
            } else {
                $summary['bk_unmatched']++;
            }

            $matchedRows[] = [
                'no'           => $row['no'],
                'class_raw'    => $row['class_raw'],
                'class'        => $matchedClass ? [
                    'id'            => $matchedClass->id,
                    'class_name'    => $matchedClass->class_name,
                    'academic_year' => $matchedClass->academic_year ?? '2026/2027',
                ] : null,
                'wali_raw'     => $row['wali_raw'],
                'wali_status'  => $waliMatch['category'],
                'wali_user'    => $waliMatch['user'] ? [
                    'id'       => $waliMatch['user']->id,
                    'username' => $waliMatch['user']->username,
                    'fullname' => $waliMatch['user']->fullname,
                ] : null,
                'wali_score'   => $waliMatch['score'],
                'wali_cands'   => $waliMatch['candidates'],
                'bk_raw'       => $row['bk_raw'],
                'bk_status'    => $bkMatch['category'],
                'bk_user'      => $bkMatch['user'] ? [
                    'id'       => $bkMatch['user']->id,
                    'username' => $bkMatch['user']->username,
                    'fullname' => $bkMatch['user']->fullname,
                ] : null,
                'bk_score'     => $bkMatch['score'],
                'bk_cands'     => $bkMatch['candidates'],
            ];
        }

        $previewToken = 'homeroom_import_' . Str::random(32);
        $payload = [
            'token'      => $previewToken,
            'summary'    => $summary,
            'rows'       => $matchedRows,
            'created_at' => now('Asia/Jakarta')->toIso8601String(),
        ];

        Cache::put($previewToken, $payload, now()->addHours(2));

        return $payload;
    }

    /**
     * Commit confirmed homeroom and counselor assignments into database within a transaction.
     */
    public function commit(string $token, array $confirmedWali = [], array $confirmedBk = []): array
    {
        $payload = Cache::get($token);
        if (!$payload) {
            throw new \RuntimeException('Sesi preview import sudah kedaluwarsa atau tidak valid. Silakan unggah kembali berkas.');
        }

        $rows = $payload['rows'] ?? [];
        $applied = [
            'homeroom_assigned' => 0,
            'counselor_assigned'=> 0,
            'skipped_rows'      => 0,
            'errors'            => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $classId = $row['class']['id'] ?? null;
                if (!$classId) {
                    $applied['skipped_rows']++;
                    $applied['errors'][] = "Baris {$row['no']}: Kelas '{$row['class_raw']}' tidak ditemukan di database.";
                    continue;
                }

                $academicYear = $row['class']['academic_year'] ?? '2026/2027';

                // Resolve Wali Kelas
                $waliId = null;
                if ($row['wali_status'] === 'matched') {
                    $waliId = $row['wali_user']['id'] ?? null;
                } elseif (isset($confirmedWali[$index]) && !empty($confirmedWali[$index])) {
                    $waliId = (int) $confirmedWali[$index];
                }

                if ($waliId) {
                    HomeroomAssignment::updateOrCreate(
                        ['class_id' => $classId],
                        [
                            'user_id'       => $waliId,
                            'academic_year' => $academicYear,
                        ]
                    );
                    $applied['homeroom_assigned']++;
                }

                // Resolve Guru BK
                $bkId = null;
                if ($row['bk_status'] === 'matched') {
                    $bkId = $row['bk_user']['id'] ?? null;
                } elseif (isset($confirmedBk[$index]) && !empty($confirmedBk[$index])) {
                    $bkId = (int) $confirmedBk[$index];
                }

                if ($bkId) {
                    CounselorAssignment::updateOrCreate(
                        [
                            'user_id'  => $bkId,
                            'class_id' => $classId,
                        ],
                        [
                            'academic_year' => $academicYear,
                        ]
                    );
                    $applied['counselor_assigned']++;
                }
            }

            DB::commit();
            Cache::forget($token);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $applied;
    }

    /**
     * Detect header row and extract valid data rows.
     */
    private function extractDataRows(array $rawRows): array
    {
        $headerColMap = null;
        $dataRows = [];

        foreach ($rawRows as $row) {
            if ($headerColMap === null) {
                $upper = array_map(fn($v) => strtoupper(trim((string) $v)), $row);
                $tempMap = [];
                foreach ($upper as $colLetter => $headerName) {
                    if ($headerName === '') {
                        continue;
                    }
                    if ($headerName === 'NO' || $headerName === 'NO.') {
                        $tempMap['NO'] = $colLetter;
                    } elseif (str_contains($headerName, 'WALI')) {
                        $tempMap['WALI'] = $colLetter;
                    } elseif (str_contains($headerName, 'BK') || str_contains($headerName, 'KONSELOR') || str_contains($headerName, 'BIMBINGAN')) {
                        $tempMap['BK'] = $colLetter;
                    } elseif (str_contains($headerName, 'KELAS') || str_contains($headerName, 'ROMBEL')) {
                        $tempMap['KELAS'] = $colLetter;
                    }
                }

                // Header must have both KELAS and WALI in distinct columns (ignores document title banner)
                if (isset($tempMap['KELAS']) && isset($tempMap['WALI']) && $tempMap['KELAS'] !== $tempMap['WALI']) {
                    $headerColMap = $tempMap;
                }
                continue;
            }

            $classRaw = trim((string) ($row[$headerColMap['KELAS'] ?? ''] ?? ''));
            // Skip empty rows, summary rows, legends, or footer signatures
            if ($classRaw === ''
                || str_contains($classRaw, '=')
                || str_starts_with(strtoupper($classRaw), 'JUMLAH')
                || str_starts_with(strtoupper($classRaw), 'TOTAL')
                || str_starts_with(strtoupper($classRaw), 'KETERANGAN')
                || str_starts_with(strtoupper($classRaw), 'DITETAPKAN')
            ) {
                continue;
            }

            $waliRaw = trim((string) ($row[$headerColMap['WALI'] ?? ''] ?? ''));
            $bkRaw = trim((string) ($row[$headerColMap['BK'] ?? ''] ?? ''));

            // Must have at least a class and one teacher assignment
            if (empty($waliRaw) && empty($bkRaw)) {
                continue;
            }

            $dataRows[] = [
                'no'        => trim((string) ($row[$headerColMap['NO'] ?? ''] ?? '')),
                'class_raw' => $classRaw,
                'wali_raw'  => $waliRaw,
                'bk_raw'    => $bkRaw,
            ];
        }

        return $dataRows;
    }

    /**
     * Match raw class string against database classes with normalization.
     */
    public function matchClass(string $rawName, Collection $existingClasses): ?SchoolClass
    {
        $clean = trim($rawName);
        if ($clean === '') {
            return null;
        }

        // 1. Exact match
        $exact = $existingClasses->first(fn($c) => strcasecmp($c->class_name, $clean) === 0);
        if ($exact) {
            return $exact;
        }

        // 2. Normalized compact match: strip non-alphanumeric and uppercase
        $compactRaw = preg_replace('/[^A-Z0-9]/', '', strtoupper($clean));

        // Handle grade digit to Roman (10 -> X, 11 -> XI, 12 -> XII)
        $compactRawWithRoman = $compactRaw;
        if (str_starts_with($compactRaw, '10')) {
            $compactRawWithRoman = 'X' . substr($compactRaw, 2);
        } elseif (str_starts_with($compactRaw, '11')) {
            $compactRawWithRoman = 'XI' . substr($compactRaw, 2);
        } elseif (str_starts_with($compactRaw, '12')) {
            $compactRawWithRoman = 'XII' . substr($compactRaw, 2);
        }

        foreach ($existingClasses as $c) {
            $compactClass = preg_replace('/[^A-Z0-9]/', '', strtoupper($c->class_name));
            if ($compactClass === $compactRaw || $compactClass === $compactRawWithRoman) {
                return $c;
            }
        }

        return null;
    }

    /**
     * Match a raw teacher name against existing teachers.
     */
    public function matchTeacher(string $rawName, Collection $existingTeachers): array
    {
        $cleanRaw = trim($rawName);
        if ($cleanRaw === '' || $cleanRaw === '-' || $cleanRaw === '0') {
            return [
                'category'   => 'unmatched',
                'user'       => null,
                'score'      => 0.0,
                'candidates' => [],
            ];
        }

        $normalizedInput = $this->employeeService->normalizeName($cleanRaw);

        // 1. Exact match by normalized name
        $exactMatches = $existingTeachers->filter(function ($t) use ($normalizedInput) {
            return $this->employeeService->normalizeName($t->fullname) === $normalizedInput;
        });

        if ($exactMatches->count() === 1) {
            return [
                'category'   => 'matched',
                'user'       => $exactMatches->first(),
                'score'      => 100.0,
                'candidates' => [],
            ];
        }

        if ($exactMatches->count() > 1) {
            $candidates = $exactMatches->values()->map(function ($t) {
                $nipDigits = !empty($t->nip) ? preg_replace('/\D/', '', $t->nip) : '';
                return [
                    'user'      => $t,
                    'score'     => 100.0,
                    'nip_last4' => strlen($nipDigits) >= 4 ? substr($nipDigits, -4) : '-',
                    'subject'   => 'Guru',
                ];
            })->all();

            return [
                'category'   => 'ambiguous',
                'user'       => null,
                'score'      => 100.0,
                'candidates' => $candidates,
            ];
        }

        // 2. Token-wise / fuzzy matching
        $fuzzyCandidates = [];
        $ambiguousCandidates = [];

        foreach ($existingTeachers as $t) {
            $cmp = $this->employeeService->compareNamesTokenWise($cleanRaw, $t->fullname);
            $nipDigits = !empty($t->nip) ? preg_replace('/\D/', '', $t->nip) : '';
            $nipLast4 = strlen($nipDigits) >= 4 ? substr($nipDigits, -4) : '-';

            if ($cmp['status'] === 'fuzzy') {
                $fuzzyCandidates[] = [
                    'user'      => $t,
                    'score'     => $cmp['score'],
                    'nip_last4' => $nipLast4,
                    'subject'   => 'Guru',
                ];
            } elseif ($cmp['status'] === 'ambiguous') {
                $ambiguousCandidates[] = [
                    'user'      => $t,
                    'score'     => $cmp['score'],
                    'nip_last4' => $nipLast4,
                    'subject'   => 'Guru',
                ];
            }
        }

        if (count($fuzzyCandidates) === 1 && empty($ambiguousCandidates)) {
            return [
                'category'   => 'fuzzy',
                'user'       => $fuzzyCandidates[0]['user'],
                'score'      => $fuzzyCandidates[0]['score'],
                'candidates' => $fuzzyCandidates,
            ];
        }

        if (count($fuzzyCandidates) > 1 || !empty($ambiguousCandidates)) {
            $merged = array_merge($fuzzyCandidates, $ambiguousCandidates);
            usort($merged, fn($a, $b) => $b['score'] <=> $a['score']);

            return [
                'category'   => 'ambiguous',
                'user'       => null,
                'score'      => $merged[0]['score'] ?? 0.0,
                'candidates' => $merged,
            ];
        }

        return [
            'category'   => 'unmatched',
            'user'       => null,
            'score'      => 0.0,
            'candidates' => [],
        ];
    }
}
