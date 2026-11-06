<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EmployeeImportService
{
    public const FUZZY_THRESHOLD = 85.0;

    /**
     * Parse spreadsheet file, match rows against database, and generate preview without DB modifications.
     */
    public function generatePreview(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $this->findGuruSheet($spreadsheet);

        if (!$sheet) {
            throw new \InvalidArgumentException("Sheet 'Guru' tidak ditemukan di dalam berkas Excel.");
        }

        $rawRows = $sheet->toArray(null, true, true, true);
        $parsedRows = $this->extractDataRows($rawRows);

        $existingTeachers = User::where('role_id', 2)->get();
        $matchedRows = [];
        $summary = [
            'total_rows' => count($parsedRows),
            'matched'    => 0,
            'fuzzy'      => 0,
            'new'        => 0,
            'ambiguous'  => 0,
            'no_email'   => 0,
        ];
        $noEmailList = [];

        foreach ($parsedRows as $row) {
            $matchResult = $this->matchTeacher($row, $existingTeachers);
            $category = $matchResult['category'];
            $summary[$category]++;

            $hasEmail = !empty($row['email']);
            if (!$hasEmail) {
                $summary['no_email']++;
                $noEmailList[] = [
                    'nip'  => $row['nip'],
                    'name' => $row['name'],
                ];
            }

            $matchedRows[] = array_merge($row, [
                'status'         => $category,
                'matched_user'   => $matchResult['user'] ? [
                    'id'       => $matchResult['user']->id,
                    'username' => $matchResult['user']->username,
                    'fullname' => $matchResult['user']->fullname,
                    'nip'      => $matchResult['user']->nip,
                ] : null,
                'match_score'    => $matchResult['score'],
                'candidate_list' => $matchResult['candidates'],
            ]);
        }

        $previewToken = 'emp_import_' . Str::random(32);
        $payload = [
            'token'         => $previewToken,
            'summary'       => $summary,
            'rows'          => $matchedRows,
            'no_email_list' => $noEmailList,
            'created_at'    => now('Asia/Jakarta')->toIso8601String(),
        ];

        Cache::put($previewToken, $payload, now()->addHours(2));

        return $payload;
    }

    /**
     * Commit confirmed import into database within a transaction.
     */
    public function commit(string $token, array $confirmedFuzzy = []): array
    {
        $payload = Cache::get($token);
        if (!$payload) {
            throw new \RuntimeException('Sesi preview import sudah kedaluwarsa atau tidak valid. Silakan unggah kembali berkas.');
        }

        $rows = $payload['rows'] ?? [];
        $applied = [
            'updated' => 0,
            'created' => 0,
            'skipped' => 0,
        ];

        DB::beginTransaction();
        try {
            // Re-fetch existing teachers to guarantee fresh state
            $allUsernames = User::pluck('username')->all();

            foreach ($rows as $index => $row) {
                $status = $row['status'];

                // Handle confirmed fuzzy match if provided by operator
                $targetUserId = null;
                if ($status === 'matched') {
                    $targetUserId = $row['matched_user']['id'] ?? null;
                } elseif ($status === 'fuzzy' && isset($confirmedFuzzy[$index])) {
                    $targetUserId = (int) $confirmedFuzzy[$index];
                }

                if ($targetUserId) {
                    $user = User::find($targetUserId);
                    if ($user) {
                        $user->fullname = $row['name'];
                        if (!empty($row['nip'])) {
                            $user->nip = $row['nip'];
                        }
                        if (!empty($row['email']) && empty($user->email)) {
                            $user->email = $row['email'];
                        }
                        $user->save();
                        $applied['updated']++;
                        continue;
                    }
                }

                if ($status === 'new' || ($status === 'fuzzy' && !isset($confirmedFuzzy[$index]))) {
                    // Create new teacher account with randomized password and must_change_password
                    $cleanName = $this->normalizeName($row['name']);
                    $baseSlug = Str::slug($cleanName) ?: 'guru';
                    $finalUsername = $baseSlug;
                    $counter = 1;
                    while (in_array($finalUsername, $allUsernames, true)) {
                        $counter++;
                        $finalUsername = "{$baseSlug}-{$counter}";
                    }
                    $allUsernames[] = $finalUsername;

                    User::create([
                        'username'             => $finalUsername,
                        'fullname'             => $row['name'],
                        'nip'                  => !empty($row['nip']) ? $row['nip'] : null,
                        'email'                => !empty($row['email']) ? $row['email'] : null,
                        'role_id'              => 2, // Guru
                        'password'             => Hash::make(Str::random(24)),
                        'must_change_password' => true,
                    ]);

                    $applied['created']++;
                } else {
                    $applied['skipped']++;
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
     * Find sheet named 'Guru' (case-insensitive).
     */
    private function findGuruSheet(Spreadsheet $spreadsheet)
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (strtolower(trim($sheet->getTitle())) === 'guru') {
                return $sheet;
            }
        }
        return null;
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
                // Check if this row is the header
                $upper = array_map(fn($v) => strtoupper(trim((string) $v)), $row);
                if (in_array('NIP', $upper, true) && in_array('NAMA', $upper, true)) {
                    $headerColMap = [];
                    foreach ($upper as $colLetter => $headerName) {
                        if ($headerName !== '') {
                            $headerColMap[$headerName] = $colLetter;
                        }
                    }
                }
                continue;
            }

            // Extract row data using column map
            $name = trim((string) ($row[$headerColMap['NAMA'] ?? ''] ?? ''));
            if ($name === '' || str_starts_with(strtoupper($name), 'JUMLAH')) {
                continue;
            }

            $nipRaw = trim((string) ($row[$headerColMap['NIP'] ?? ''] ?? ''));
            $nip = preg_replace('/[^\d]/', '', $nipRaw);

            $dataRows[] = [
                'no'        => trim((string) ($row[$headerColMap['NO'] ?? ''] ?? '')),
                'nip'       => $nip !== '' ? $nip : null,
                'name'      => $name,
                'gender'    => trim((string) ($row[$headerColMap['JK'] ?? ''] ?? '')),
                'rank'      => trim((string) ($row[$headerColMap['GOLONGAN'] ?? ''] ?? '')),
                'position'  => trim((string) ($row[$headerColMap['JABATAN'] ?? ''] ?? '')),
                'education' => trim((string) ($row[$headerColMap['PENDIDIKAN'] ?? ''] ?? '')),
                'subject'   => trim((string) ($row[$headerColMap['MAPEL'] ?? ''] ?? '')),
                'email'     => isset($headerColMap['EMAIL']) ? trim((string) ($row[$headerColMap['EMAIL']] ?? '')) : null,
            ];
        }

        return $dataRows;
    }

    /**
     * Match a row against database teachers.
     */
    public function matchTeacher(array $row, Collection $existingTeachers): array
    {
        // 1. Match by NIP if available
        if (!empty($row['nip'])) {
            $nipMatches = $existingTeachers->filter(fn($t) => !empty($t->nip) && $t->nip === $row['nip']);
            if ($nipMatches->count() === 1) {
                return [
                    'category'   => 'matched',
                    'user'       => $nipMatches->first(),
                    'score'      => 100.0,
                    'candidates' => [],
                ];
            }
        }

        // 2. Exact match by normalized name
        $cleanRowName = $this->normalizeName($row['name']);
        $exactMatches = $existingTeachers->filter(function ($t) use ($cleanRowName) {
            return $this->normalizeName($t->fullname) === $cleanRowName;
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
            return [
                'category'   => 'ambiguous',
                'user'       => null,
                'score'      => 100.0,
                'candidates' => $exactMatches->values()->all(),
            ];
        }

        // 3. Fuzzy matching via similar_text
        $candidates = [];
        foreach ($existingTeachers as $t) {
            $cleanTName = $this->normalizeName($t->fullname);
            similar_text($cleanRowName, $cleanTName, $percent);
            if ($percent >= self::FUZZY_THRESHOLD) {
                $candidates[] = [
                    'user'  => $t,
                    'score' => round($percent, 2),
                ];
            }
        }

        if (count($candidates) === 1) {
            return [
                'category'   => 'fuzzy',
                'user'       => $candidates[0]['user'],
                'score'      => $candidates[0]['score'],
                'candidates' => $candidates,
            ];
        }

        if (count($candidates) > 1) {
            return [
                'category'   => 'ambiguous',
                'user'       => null,
                'score'      => $candidates[0]['score'],
                'candidates' => $candidates,
            ];
        }

        // 4. No match found -> new teacher
        return [
            'category'   => 'new',
            'user'       => null,
            'score'      => 0.0,
            'candidates' => [],
        ];
    }

    /**
     * Clean and normalize Indonesian names by uppercase, removing titles and non-letter chars.
     */
    public function normalizeName(string $name): string
    {
        // 1. Take part before comma (degrees)
        $parts = explode(',', $name);
        $namePart = $parts[0] ?? '';

        // 2. Remove common prefixes
        $namePart = preg_replace('/^(Pak|Bu|Drs\.|Dra\.|Dr\.|Prof\.|Ir\.|H\.|Hj\.)\s+/i', '', $namePart);

        // 3. Keep only letters and spaces, uppercase
        $clean = preg_replace('/[^a-zA-Z\s]/', '', $namePart);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        return strtoupper($clean);
    }
}
