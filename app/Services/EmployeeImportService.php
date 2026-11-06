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

        // Precompute source name frequencies for ambiguity detection
        $sourceNameCounts = [];
        foreach ($parsedRows as $r) {
            $norm = $this->normalizeName($r['name']);
            $sourceNameCounts[$norm] = ($sourceNameCounts[$norm] ?? 0) + 1;
        }

        $existingTeachers = User::where('role_id', 2)->get();

        // Precompute DB null-NIP frequencies for ambiguity detection
        $dbNullNipCounts = [];
        foreach ($existingTeachers as $t) {
            if (empty($t->nip)) {
                $norm = $this->normalizeName($t->fullname);
                $dbNullNipCounts[$norm] = ($dbNullNipCounts[$norm] ?? 0) + 1;
            }
        }

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
            $matchResult = $this->matchTeacher($row, $existingTeachers, $sourceNameCounts, $dbNullNipCounts);
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
                'title_warning'  => $matchResult['title_warning'] ?? null,
            ]);
        }

        // Collision check: only flag if multiple source rows share the EXACT same NIP
        $nipSourceMap = [];
        foreach ($matchedRows as $idx => $r) {
            $nip = $r['nip'] ?? null;
            if (!empty($nip)) {
                $nipSourceMap[$nip][] = $idx;
            }
        }
        foreach ($nipSourceMap as $nip => $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $idx) {
                    $oldStatus = $matchedRows[$idx]['status'];
                    if ($oldStatus !== 'ambiguous') {
                        $summary[$oldStatus]--;
                        $summary['ambiguous']++;
                        $matchedRows[$idx]['status'] = 'ambiguous';
                        $matchedRows[$idx]['ambiguous_reason'] = "Tabrakan: Lebih dari satu baris sumber memiliki NIP yang sama ({$nip})";
                    }
                }
            }
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

                    if (!in_array($baseSlug, $allUsernames, true)) {
                        $finalUsername = $baseSlug;
                    } else {
                        $nipDigits = !empty($row['nip']) ? preg_replace('/\D/', '', $row['nip']) : '';
                        $nipLast4 = strlen($nipDigits) >= 4 ? substr($nipDigits, -4) : '';
                        $candidateWithNip = $nipLast4 !== '' ? "{$baseSlug}-{$nipLast4}" : '';

                        if ($candidateWithNip !== '' && !in_array($candidateWithNip, $allUsernames, true)) {
                            $finalUsername = $candidateWithNip;
                        } else {
                            $counter = 1;
                            do {
                                $counter++;
                                $finalUsername = "{$baseSlug}-{$counter}";
                            } while (in_array($finalUsername, $allUsernames, true));
                        }
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
    public function matchTeacher(
        array $row, 
        Collection $existingTeachers, 
        array $sourceNameCounts = [], 
        array $dbNullNipCounts = []
    ): array
    {
        $hasNip = !empty($row['nip']);

        // 1. Match by NIP if available
        if ($hasNip) {
            $nipMatches = $existingTeachers->filter(fn($t) => !empty($t->nip) && (string)$t->nip === (string)$row['nip']);
            if ($nipMatches->count() === 1) {
                return [
                    'category'   => 'matched',
                    'match_type' => 'nip',
                    'user'       => $nipMatches->first(),
                    'score'      => 100.0,
                    'candidates' => [],
                ];
            }
            if ($nipMatches->count() > 1) {
                return [
                    'category'         => 'ambiguous',
                    'match_type'       => 'nip_collision',
                    'user'             => null,
                    'score'            => 100.0,
                    'candidates'       => $nipMatches->values()->all(),
                    'ambiguous_reason' => 'NIP ditemukan ganda di basis data.',
                ];
            }

            // If source row has NIP N, but no user has nip == N:
            // Candidates for name matching can ONLY come from users where nip is NULL.
            // Users with a different NIP are NEVER candidates.
            $candidatePool = $existingTeachers->filter(fn($t) => empty($t->nip));
        } else {
            // Source row without NIP: candidate pool from users with null NIP
            $candidatePool = $existingTeachers->filter(fn($t) => empty($t->nip));
        }

        $cleanRowName = $this->normalizeName($row['name']);

        // 2. Exact match by normalized name
        $exactMatches = $candidatePool->filter(function ($t) use ($cleanRowName) {
            return $this->normalizeName($t->fullname) === $cleanRowName;
        });

        if ($exactMatches->count() === 1) {
            $matchedUser = $exactMatches->first();
            $isUniqueInSource = ($sourceNameCounts[$cleanRowName] ?? 1) === 1;
            $isUniqueInDb = ($dbNullNipCounts[$cleanRowName] ?? 1) === 1;

            if ($isUniqueInSource && $isUniqueInDb) {
                return [
                    'category'   => 'matched',
                    'match_type' => 'exact_name',
                    'user'       => $matchedUser,
                    'score'      => 100.0,
                    'candidates' => [],
                ];
            }

            return [
                'category'         => 'ambiguous',
                'match_type'       => 'exact_name_multiple',
                'user'             => null,
                'score'            => 100.0,
                'candidates'       => [
                    [
                        'user'          => $matchedUser,
                        'score'         => 100.0,
                        'title_warning' => $this->checkDegreeMismatch($row['name'], $matchedUser->fullname),
                    ]
                ],
                'ambiguous_reason' => 'Nama persis ditemukan lebih dari satu di berkas sumber atau di basis data.',
            ];
        }

        if ($exactMatches->count() > 1) {
            return [
                'category'         => 'ambiguous',
                'match_type'       => 'exact_name_multiple',
                'user'             => null,
                'score'            => 100.0,
                'candidates'       => $exactMatches->values()->map(fn($u) => [
                    'user'          => $u,
                    'score'         => 100.0,
                    'title_warning' => $this->checkDegreeMismatch($row['name'], $u->fullname),
                ])->all(),
                'ambiguous_reason' => 'Ditemukan lebih dari satu guru dengan nama persis di basis data.',
            ];
        }

        // 3. Token-wise / fuzzy matching
        $fuzzyCandidates = [];
        $ambiguousCandidates = [];

        foreach ($candidatePool as $t) {
            $cmp = $this->compareNamesTokenWise($row['name'], $t->fullname);
            $degreeWarning = $this->checkDegreeMismatch($row['name'], $t->fullname);

            if ($cmp['status'] === 'fuzzy' && $cmp['score'] >= self::FUZZY_THRESHOLD) {
                $fuzzyCandidates[] = [
                    'user'          => $t,
                    'score'         => $cmp['score'],
                    'title_warning' => $degreeWarning,
                ];
            } elseif ($cmp['status'] === 'ambiguous' && $cmp['score'] >= 70.0) {
                $ambiguousCandidates[] = [
                    'user'          => $t,
                    'score'         => $cmp['score'],
                    'title_warning' => $degreeWarning,
                ];
            }
        }

        if (count($fuzzyCandidates) === 1 && empty($ambiguousCandidates)) {
            return [
                'category'      => 'fuzzy',
                'user'          => $fuzzyCandidates[0]['user'],
                'score'         => $fuzzyCandidates[0]['score'],
                'candidates'    => $fuzzyCandidates,
                'title_warning' => $fuzzyCandidates[0]['title_warning'] ?? null,
            ];
        }

        if (count($fuzzyCandidates) > 1 || !empty($ambiguousCandidates)) {
            $mergedCandidates = array_merge($fuzzyCandidates, $ambiguousCandidates);
            usort($mergedCandidates, fn($a, $b) => $b['score'] <=> $a['score']);

            return [
                'category'   => 'ambiguous',
                'user'       => null,
                'score'      => $mergedCandidates[0]['score'] ?? 0.0,
                'candidates' => $mergedCandidates,
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
     * Compare two names token-wise.
     * Prevents false matches where first names are identical but last names differ.
     * Score < 70% returns status 'none' (drops to NEW).
     * AMBIGUOUS only if score >= 70% with differing last token.
     */
    public function compareNamesTokenWise(string $nameA, string $nameB): array
    {
        $normA = $this->normalizeName($nameA);
        $normB = $this->normalizeName($nameB);

        if ($normA === '' || $normB === '') {
            return ['status' => 'none', 'score' => 0.0];
        }

        if ($normA === $normB) {
            return ['status' => 'exact', 'score' => 100.0];
        }

        // Check whitespace-collapsed match (e.g. YULITA EKAWINAJENG vs YULITA EKA WINAJENG)
        if (str_replace(' ', '', $normA) === str_replace(' ', '', $normB)) {
            return ['status' => 'fuzzy', 'score' => 99.0];
        }

        $tokensA = explode(' ', $normA);
        $tokensB = explode(' ', $normB);

        // If first token is identical, but last token differs:
        // AMBIGUOUS only if last token similarity is >= 70% and < 80%.
        // If last token similarity < 70%, it is NOT ambiguous (treated as separate names, drops to NEW).
        if (count($tokensA) > 1 && count($tokensB) > 1) {
            similar_text($tokensA[0], $tokensB[0], $firstSim);
            $lastA = end($tokensA);
            $lastB = end($tokensB);

            if ($firstSim >= 90.0 && $lastA !== $lastB) {
                similar_text($lastA, $lastB, $lastSim);
                if ($lastSim >= 70.0 && $lastSim < 80.0) {
                    return ['status' => 'ambiguous', 'score' => round($lastSim, 2)];
                }
            }
        }

        // Token-wise comparison when token counts match
        if (count($tokensA) === count($tokensB)) {
            $tokenSims = [];
            $allAboveMin = true;
            for ($i = 0; $i < count($tokensA); $i++) {
                similar_text($tokensA[$i], $tokensB[$i], $sim);
                if ($sim < 70.0) {
                    $allAboveMin = false;
                }
                $tokenSims[] = $sim;
            }
            $avgSim = array_sum($tokenSims) / count($tokenSims);
            if ($allAboveMin && $avgSim >= self::FUZZY_THRESHOLD) {
                return ['status' => 'fuzzy', 'score' => round($avgSim, 2)];
            }
        }

        // Overall similarity
        similar_text($normA, $normB, $overallSim);
        if ($overallSim >= self::FUZZY_THRESHOLD) {
            return ['status' => 'fuzzy', 'score' => round($overallSim, 2)];
        }

        return ['status' => 'none', 'score' => round($overallSim, 2)];
    }

    /**
     * Extract academic degrees and titles from full name string.
     */
    public function extractDegrees(string $name): array
    {
        $degrees = [];
        $patterns = [
            'S\.?Pd\.?Kor' => 'S.Pd.Kor',
            'S\.?Pd\.?Si'  => 'S.Pd.Si',
            'S\.?Pd\.?I'   => 'S.Pd.I',
            'M\.?S\.?I'    => 'M.S.I',
            'M\.?Eng'      => 'M.Eng',
            'M\.?Acc'      => 'M.Acc',
            'M\.?Kom'      => 'M.Kom',
            'S\.?Kom'      => 'S.Kom',
            'S\.?Sos'      => 'S.Sos',
            'S\.?Pd'       => 'S.Pd',
            'M\.?Pd'       => 'M.Pd',
            'M\.?Si'       => 'M.Si',
            'S\.?T'        => 'S.T',
            'S\.?S'        => 'S.S',
            'M\.?M'        => 'M.M',
            'M\.?T'        => 'M.T',
            'Drs\.?'       => 'Drs.',
            'Dra\.?'       => 'Dra.',
        ];

        foreach ($patterns as $regex => $standard) {
            if (preg_match('/\b' . $regex . '\b/i', $name)) {
                $degrees[] = $standard;
            }
        }

        return array_values(array_unique($degrees));
    }

    /**
     * Check if source name and candidate name have differing academic degrees.
     */
    public function checkDegreeMismatch(string $sourceName, string $candidateName): ?string
    {
        $sourceDegrees = $this->extractDegrees($sourceName);
        $candidateDegrees = $this->extractDegrees($candidateName);

        if (empty($sourceDegrees) && empty($candidateDegrees)) {
            return null;
        }

        sort($sourceDegrees);
        sort($candidateDegrees);

        if ($sourceDegrees !== $candidateDegrees) {
            $srcStr = !empty($sourceDegrees) ? implode(', ', $sourceDegrees) : 'Tanpa Gelar';
            $candStr = !empty($candidateDegrees) ? implode(', ', $candidateDegrees) : 'Tanpa Gelar';
            return "Peringatan: Gelar berbeda (Sumber: {$srcStr} vs DB: {$candStr})";
        }

        return null;
    }

    /**
     * Clean and normalize Indonesian names:
     * Uppercase, strip titles (Drs, Dra, Prof, Dr, Raden Rara, etc.), degrees (S.Pd, M.Pd, etc.), dots, commas, double spaces.
     */
    public function normalizeName(string $name): string
    {
        $clean = str_replace(["`", "’", "'", "\"", ","], " ", $name);

        $prefixes = [
            '\bRADEN\s+RARA\b', '\bRR\b', '\bDRA\b', '\bDRS\b', '\bPROF\b', '\bDR\b', '\bIR\b', '\bHJ\b', '\bH\b', '\bPAK\b', '\bBU\b'
        ];
        $clean = preg_replace('/([A-Za-z])\.([A-Za-z])/', '$1 $2', $clean);
        $clean = str_replace('.', ' ', $clean);

        foreach ($prefixes as $p) {
            $clean = preg_replace('/^\s*' . $p . '\s+/i', ' ', $clean);
        }

        $degrees = [
            'S\s*PD\s*KOR', 'S\s*PD\s*SI', 'S\s*PD\s*I', 'M\s*S\s*I', 'M\s*ENG', 'M\s*ACC', 'M\s*KOM', 'S\s*KOM',
            'S\s*SOS', 'S\s*PD', 'M\s*PD', 'M\s*SI', 'M\s*AG', 'S\s*AG', 'S\s*SI', 'S\s*PT', 'S\s*IP', 'S\s*H',
            'S\s*E', 'S\s*T', 'S\s*S', 'M\s*M', 'M\s*T'
        ];
        foreach ($degrees as $d) {
            $clean = preg_replace('/\b' . $d . '\b/i', ' ', $clean);
        }

        $clean = preg_replace('/[^a-zA-Z\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        return strtoupper($clean);
    }
}
