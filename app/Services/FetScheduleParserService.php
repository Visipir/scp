<?php

namespace App\Services;

use App\Http\Controllers\ScheduleImportController;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FetScheduleParserService
{
    public function __construct(
        private readonly EmployeeImportService $employeeService,
        private readonly ScheduleConflictService $conflictService
    ) {}

    /**
     * Parse timetable file (XML, CSV, or PDF), aggregate slots, check conflicts and lock, generate preview.
     */
    public function generatePreview(UploadedFile $file, string $cycleType = 'Putaran 2'): array
    {
        $this->ensureScheduleUnlocked();

        $extension = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        $rawSlots = match ($extension) {
            'xml' => $this->parseXml($realPath),
            'csv' => $this->parseCsv($realPath),
            'pdf' => $this->parsePdf($realPath),
            default => throw new \InvalidArgumentException("Format berkas .{$extension} tidak didukung. Gunakan .xml, .csv, atau .pdf."),
        };

        if (empty($rawSlots)) {
            throw new \RuntimeException('Tidak ditemukan data jadwal pelajaran yang valid di dalam berkas.');
        }

        // Resolve master data
        $existingTeachers = User::where('role_id', 2)->get();
        $existingClasses = SchoolClass::all();
        $existingRooms = Room::all();

        $resolvedSlots = [];
        $unmappedTeachers = [];

        foreach ($rawSlots as $slot) {
            $teacher = $this->resolveTeacher($slot['teacher_raw'], $slot['teacher_code'] ?? null, $existingTeachers);
            if (!$teacher && !empty($slot['teacher_raw'])) {
                $unmappedTeachers[$slot['teacher_raw']] = true;
            }

            $class = $this->resolveClass($slot['class_raw'], $cycleType, $existingClasses);
            $room = $this->resolveRoom($slot['room_raw'], $existingRooms);

            $resolvedSlots[] = [
                'day_of_week'  => $this->normalizeDay($slot['day']),
                'period'       => (int) $slot['period'],
                'teacher'      => $teacher,
                'teacher_raw'  => $slot['teacher_raw'],
                'teacher_code' => $slot['teacher_code'] ?? null,
                'class'        => $class,
                'class_raw'    => $slot['class_raw'],
                'room'         => $room,
                'room_raw'     => $slot['room_raw'],
                'subject'      => trim($slot['subject'] ?? 'Pelajaran'),
            ];
        }

        // Aggregate consecutive slots
        $aggregated = $this->aggregateConsecutiveSlots($resolvedSlots, $cycleType);

        // Check conflicts against existing DB schedules (excluding Putaran being replaced) and internal collisions
        $conflicts = [];
        $validSchedules = [];
        $byDay = [
            'Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0,
        ];

        foreach ($aggregated as $item) {
            $day = $item['day_of_week'];
            if (isset($byDay[$day])) {
                $byDay[$day]++;
            }

            // Conflict check
            $conflictErr = null;
            if (!empty($item['teacher']['id']) && !empty($item['class']['id'])) {
                $teacherId = $item['teacher']['id'];
                $classId = $item['class']['id'];
                $roomId = $item['room']['id'] ?? null;

                // Check conflict against DB schedules that HAVE journals (which cannot be deleted)
                $dbConflict = Schedule::where('day_of_week', $item['day_of_week'])
                    ->where('cycle_type', $cycleType)
                    ->whereHas('journals')
                    ->where('start_time', '<', $item['end_time'])
                    ->where('end_time', '>', $item['start_time'])
                    ->where(function ($q) use ($teacherId, $classId, $roomId) {
                        $q->where('teacher_id', $teacherId)
                          ->orWhere('class_id', $classId)
                          ->when($roomId, fn($sub) => $sub->orWhere('room_id', $roomId));
                    })
                    ->first();

                if ($dbConflict) {
                    $conflictErr = "Bentrok dengan jadwal yang sudah memiliki jurnal (ID: {$dbConflict->id}, {$dbConflict->subject})";
                }
            }

            if ($conflictErr) {
                $conflicts[] = array_merge($item, ['conflict_reason' => $conflictErr]);
            } else {
                $validSchedules[] = $item;
            }
        }

        // Count preserved schedules with journals
        $preservedCount = Schedule::where('cycle_type', $cycleType)->has('journals')->count();

        $previewToken = 'fet_import_' . Str::random(32);
        $payload = [
            'token'           => $previewToken,
            'cycle_type'      => $cycleType,
            'summary'         => [
                'total_slots_raw'     => count($rawSlots),
                'aggregated_total'    => count($aggregated),
                'valid_count'         => count($validSchedules),
                'conflict_count'      => count($conflicts),
                'unmapped_teachers'   => array_keys($unmappedTeachers),
                'by_day'              => $byDay,
                'preserved_with_journals' => $preservedCount,
            ],
            'schedules'       => $validSchedules,
            'conflicts'       => $conflicts,
            'created_at'      => now('Asia/Jakarta')->toIso8601String(),
        ];

        Cache::put($previewToken, $payload, now()->addHours(2));

        return $payload;
    }

    /**
     * Commit preview schedules into DB.
     * Replaces schedules in cycle EXCEPT those with attached journals.
     */
    public function commit(string $token): array
    {
        $this->ensureScheduleUnlocked();

        $payload = Cache::get($token);
        if (!$payload) {
            throw new \RuntimeException('Sesi preview import sudah kedaluwarsa atau tidak valid. Silakan unggah kembali berkas.');
        }

        $cycleType = $payload['cycle_type'] ?? 'Putaran 2';
        $schedules = $payload['schedules'] ?? [];

        $applied = [
            'inserted'  => 0,
            'preserved' => 0,
            'deleted'   => 0,
        ];

        DB::beginTransaction();
        try {
            // Delete old schedules of this cycle that do NOT have journals
            $deleted = Schedule::where('cycle_type', $cycleType)
                ->doesntHave('journals')
                ->delete();
            $applied['deleted'] = $deleted;

            $applied['preserved'] = Schedule::where('cycle_type', $cycleType)->has('journals')->count();

            foreach ($schedules as $item) {
                if (empty($item['teacher']['id']) || empty($item['class']['id'])) {
                    continue;
                }

                Schedule::create([
                    'teacher_id'   => $item['teacher']['id'],
                    'class_id'     => $item['class']['id'],
                    'room_id'      => $item['room']['id'] ?? null,
                    'subject'      => $item['subject'],
                    'day_of_week'  => $item['day_of_week'],
                    'start_period' => $item['start_period'],
                    'end_period'   => $item['end_period'],
                    'start_time'   => $item['start_time'],
                    'end_time'     => $item['end_time'],
                    'cycle_type'   => $cycleType,
                ]);

                $applied['inserted']++;
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
     * Ensure schedule is not locked in system configuration.
     */
    public function ensureScheduleUnlocked(): void
    {
        $config = SystemConfiguration::first();
        if ($config && (bool) $config->is_schedule_locked) {
            throw new \DomainException('Jadwal pelajaran sedang dikunci oleh Waka Kurikulum. Import jadwal dibatalkan.');
        }
    }

    /**
     * Aggregate consecutive single periods into consolidated schedule blocks.
     */
    public function aggregateConsecutiveSlots(array $slots, string $cycleType): array
    {
        // Group by day_of_week, teacher_id, class_id, room_id, subject
        $groups = [];
        foreach ($slots as $slot) {
            if (!$slot['teacher'] || !$slot['class']) {
                continue;
            }

            $key = sprintf(
                '%s|%d|%d|%d|%s',
                $slot['day_of_week'],
                $slot['teacher']->id,
                $slot['class']->id,
                $slot['room']?->id ?? 0,
                strtolower(trim($slot['subject']))
            );

            $groups[$key][] = $slot;
        }

        $aggregated = [];
        $periodConfig = ($cycleType === 'Putaran 2')
            ? LessonHourService::PUTARAN_2_PERIODS
            : LessonHourService::getAllMatrix();

        foreach ($groups as $group) {
            // Sort group items by period ascending
            usort($group, fn($a, $b) => $a['period'] <=> $b['period']);

            $currentBlock = null;

            foreach ($group as $item) {
                if ($currentBlock === null) {
                    $currentBlock = [
                        'day_of_week'  => $item['day_of_week'],
                        'start_period' => $item['period'],
                        'end_period'   => $item['period'],
                        'teacher'      => [
                            'id'       => $item['teacher']->id,
                            'fullname' => $item['teacher']->fullname,
                            'username' => $item['teacher']->username,
                        ],
                        'class'        => [
                            'id'         => $item['class']->id,
                            'class_name' => $item['class']->class_name,
                        ],
                        'room'         => $item['room'] ? [
                            'id'        => $item['room']->id,
                            'room_name' => $item['room']->room_name,
                        ] : null,
                        'subject'      => $item['subject'],
                    ];
                    continue;
                }

                // Check if contiguous period
                if ($item['period'] === $currentBlock['end_period'] + 1) {
                    $currentBlock['end_period'] = $item['period'];
                } else {
                    // Gap detected -> commit previous block and start new
                    $this->fillTimes($currentBlock, $periodConfig);
                    $aggregated[] = $currentBlock;

                    $currentBlock = [
                        'day_of_week'  => $item['day_of_week'],
                        'start_period' => $item['period'],
                        'end_period'   => $item['period'],
                        'teacher'      => [
                            'id'       => $item['teacher']->id,
                            'fullname' => $item['teacher']->fullname,
                            'username' => $item['teacher']->username,
                        ],
                        'class'        => [
                            'id'         => $item['class']->id,
                            'class_name' => $item['class']->class_name,
                        ],
                        'room'         => $item['room'] ? [
                            'id'        => $item['room']->id,
                            'room_name' => $item['room']->room_name,
                        ] : null,
                        'subject'      => $item['subject'],
                    ];
                }
            }

            if ($currentBlock !== null) {
                $this->fillTimes($currentBlock, $periodConfig);
                $aggregated[] = $currentBlock;
            }
        }

        return $aggregated;
    }

    private function fillTimes(array &$block, array $periodConfig): void
    {
        $day = $block['day_of_week'];
        $dayPeriods = $periodConfig[$day] ?? ($periodConfig['Senin'] ?? []);

        $startP = $block['start_period'];
        $endP = $block['end_period'];

        $startTime = $dayPeriods[$startP]['start_time'] ?? sprintf('%02d:00', 7 + $startP);
        $endTime = $dayPeriods[$endP]['end_time'] ?? sprintf('%02d:45', 7 + $endP);

        $block['start_time'] = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
        $block['end_time'] = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;
    }

    /**
     * Parse XML generated by FET.
     */
    public function parseXml(string $path): array
    {
        $content = file_get_contents($path);
        $xml = @simplexml_load_string($content);
        if (!$xml) {
            throw new \RuntimeException('Gagal memproses struktur berkas FET XML.');
        }

        $slots = [];
        $activities = $xml->xpath('//Activity') ?: [];

        foreach ($activities as $act) {
            $day = (string) ($act->Day ?? $act->day ?? '');
            $hour = (string) ($act->Hour ?? $act->hour ?? '1');
            $teacher = (string) ($act->Teacher ?? $act->teacher ?? '');
            $subject = (string) ($act->Subject ?? $act->subject ?? '');
            $class = (string) ($act->Students ?? $act->students ?? '');
            $room = (string) ($act->Room ?? $act->room ?? '');

            if (empty($day) || empty($class)) {
                continue;
            }

            $period = preg_replace('/[^\d]/', '', $hour);

            $slots[] = [
                'day'          => $day,
                'period'       => !empty($period) ? (int) $period : 1,
                'teacher_raw'  => $teacher,
                'teacher_code' => is_numeric($teacher) ? $teacher : null,
                'subject'      => $subject,
                'class_raw'    => $class,
                'room_raw'     => $room,
            ];
        }

        return $slots;
    }

    /**
     * Parse CSV exported by FET.
     */
    public function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $slots = [];
        $header = null;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (!$header) {
                $header = array_map(fn($v) => strtolower(trim((string) $v)), $row);
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), ''));
            if (!$data) {
                continue;
            }

            $day = $data['day'] ?? ($data['hari'] ?? '');
            $hour = $data['hour'] ?? ($data['jam'] ?? '1');
            $teacher = $data['teacher'] ?? ($data['guru'] ?? '');
            $subject = $data['subject'] ?? ($data['mapel'] ?? ($data['mata pelajaran'] ?? ''));
            $class = $data['students'] ?? ($data['class'] ?? ($data['kelas'] ?? ''));
            $room = $data['room'] ?? ($data['ruang'] ?? ($data['ruangan'] ?? ''));

            if (empty($day) || empty($class)) {
                continue;
            }

            $period = preg_replace('/[^\d]/', '', $hour);

            $slots[] = [
                'day'          => $day,
                'period'       => !empty($period) ? (int) $period : 1,
                'teacher_raw'  => $teacher,
                'teacher_code' => is_numeric($teacher) ? $teacher : null,
                'subject'      => $subject,
                'class_raw'    => $class,
                'room_raw'     => $room,
            ];
        }

        fclose($handle);
        return $slots;
    }

    /**
     * Parse FET PDF using pdftohtml XML preserving exact tabular coordinates.
     */
    public function parsePdf(string $path): array
    {
        $cmd = sprintf('pdftohtml -xml -stdout %s', escapeshellarg($path));
        $output = shell_exec($cmd);

        if (empty($output)) {
            return [];
        }

        $xml = @simplexml_load_string($output);
        if (!$xml) {
            return [];
        }

        $pages = $xml->xpath('//page') ?: [];
        $slots = [];

        $timeSlots = [
            1 => '07:15', 2 => '08:00', 3 => '08:45', 4 => '09:45', 5 => '10:30',
            6 => '11:15', 7 => '12:30', 8 => '13:15', 9 => '14:00', 10 => '14:45',
        ];
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $classPattern = '/\b(X|XI|XII)\s+([A-Z0-9]+(?:\s+[0-9]+)?)\b/i';

        foreach ($pages as $p) {
            $pNum = (int) ($p['number'] ?? 0);
            if ($pNum <= 2) {
                continue; // Skip TOC
            }

            $texts = $p->xpath('.//text') ?: [];

            // 1. Extract Teacher Code and Name from header
            $teacherCode = null;
            $teacherName = null;
            foreach ($texts as $t) {
                $str = html_entity_decode(strip_tags($t->asXML()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $top = (int) $t['top'];
                if ($top >= 50 && $top <= 90) {
                    if (preg_match('/["\']?(\d+)[.,\s]*([^"\'\n]+)/u', $str, $m)) {
                        $teacherCode = $m[1];
                        $teacherName = trim($m[2], " \t\n\r\0\x0B\"'");
                        break;
                    }
                }
            }

            if (!$teacherCode) {
                continue;
            }

            // 2. Identify Day Column Coordinates
            $dayPositions = [];
            foreach ($texts as $t) {
                $str = trim(html_entity_decode(strip_tags($t->asXML()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $top = (int) $t['top'];
                $left = (int) $t['left'];
                $width = (int) $t['width'];
                if ($top >= 85 && $top <= 115) {
                    if (preg_match('/\bSenin\b/i', $str)) $dayPositions['Senin'] = $left;
                    if (preg_match('/\bSelasa\b/i', $str)) $dayPositions['Selasa'] = $left;
                    if (preg_match('/\bRabu\b/i', $str)) $dayPositions['Rabu'] = $left;
                    if (preg_match('/\bKamis\s+Jumat\b/i', $str)) {
                        $dayPositions['Kamis'] = $left;
                        $dayPositions['Jumat'] = $left + (int) ($width * 0.6);
                    } else {
                        if (preg_match('/\bKamis\b/i', $str)) $dayPositions['Kamis'] = $left;
                        if (preg_match('/\bJumat\b/i', $str)) $dayPositions['Jumat'] = $left;
                    }
                }
            }

            if (count($dayPositions) < 5) {
                $dayPositions = ['Senin' => 166, 'Selasa' => 334, 'Rabu' => 507, 'Kamis' => 617, 'Jumat' => 731];
            }

            // 3. Identify Time Row Coordinates
            $rowPositions = [];
            foreach ($texts as $t) {
                $str = trim(html_entity_decode(strip_tags($t->asXML()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $left = (int) $t['left'];
                if ($left >= 40 && $left <= 80) {
                    foreach ($timeSlots as $pIdx => $ts) {
                        if ($str === $ts) {
                            $rowPositions[$pIdx] = (int) $t['top'];
                        }
                    }
                }
            }

            if (count($rowPositions) < 10) {
                $rowPositions = [
                    1 => 145, 2 => 218, 3 => 290, 4 => 363, 5 => 435,
                    6 => 507, 7 => 580, 8 => 652, 9 => 725, 10 => 797,
                ];
            }

            // Calculate Day Column Boundaries
            $orderedDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
            $colBounds = [];
            for ($i = 0; $i < count($orderedDays); $i++) {
                $currDay = $orderedDays[$i];
                $currLeft = $dayPositions[$currDay];
                $minX = ($i === 0) ? 90 : ($dayPositions[$orderedDays[$i - 1]] + $currLeft) / 2;
                $maxX = ($i === count($orderedDays) - 1) ? 880 : ($currLeft + $dayPositions[$orderedDays[$i + 1]]) / 2;
                $colBounds[$currDay] = ['min' => $minX, 'max' => $maxX];
            }

            // Calculate Period Row Boundaries
            $rowBounds = [];
            for ($i = 1; $i <= 10; $i++) {
                $currTop = $rowPositions[$i];
                $minY = ($i === 1) ? 120 : ($rowPositions[$i - 1] + $currTop) / 2;
                $maxY = ($i === 10) ? 830 : ($currTop + $rowPositions[$i + 1]) / 2;
                $rowBounds[$i] = ['min' => $minY, 'max' => $maxY, 'center' => $currTop];
            }

            // Index Elements by Day and Period
            $elements = [];
            foreach ($texts as $t) {
                $str = trim(html_entity_decode(strip_tags($t->asXML()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $top = (int) $t['top'];
                $left = (int) $t['left'];

                if ($top < 120 || $top > 830 || $left < 90) {
                    continue;
                }

                $matchedDay = null;
                foreach ($colBounds as $day => $b) {
                    if ($left >= $b['min'] && $left < $b['max']) {
                        $matchedDay = $day;
                        break;
                    }
                }
                if (!$matchedDay) continue;

                $matchedP = null;
                foreach ($rowBounds as $pIdx => $b) {
                    if ($top >= $b['min'] && $top < $b['max']) {
                        $matchedP = $pIdx;
                        break;
                    }
                }
                if (!$matchedP) continue;

                $elements[$matchedDay][$matchedP][] = [
                    'text' => $str,
                    'top'  => $top,
                    'left' => $left,
                ];
            }

            // Process Slots per Day
            foreach ($orderedDays as $day) {
                $pIdx = 1;
                while ($pIdx <= 10) {
                    $items = $elements[$day][$pIdx] ?? [];
                    if (empty($items)) {
                        $pIdx++;
                        continue;
                    }

                    $isFree = false;
                    foreach ($items as $it) {
                        if (in_array($it['text'], ['---', '-x-', '--', '-'], true)) {
                            $isFree = true;
                            break;
                        }
                    }
                    if ($isFree) {
                        $pIdx++;
                        continue;
                    }

                    $foundClass = null;
                    foreach ($items as $it) {
                        if (preg_match($classPattern, $it['text'], $m)) {
                            $foundClass = trim($m[0]);
                            break;
                        }
                    }

                    if ($foundClass) {
                        $startP = $pIdx;
                        $endP = $pIdx;
                        $collectedItems = $items;

                        for ($nextP = $pIdx + 1; $nextP <= 10; $nextP++) {
                            $nextItems = $elements[$day][$nextP] ?? [];
                            if (empty($nextItems)) {
                                if ($endP - $startP < 1) {
                                    $endP = $nextP;
                                }
                                break;
                            }

                            $nextFree = false;
                            $nextHasClass = false;
                            foreach ($nextItems as $nit) {
                                if (in_array($nit['text'], ['---', '-x-', '--', '-'], true)) {
                                    $nextFree = true;
                                    break;
                                }
                                if (preg_match($classPattern, $nit['text'])) {
                                    $nextHasClass = true;
                                    break;
                                }
                            }

                            if ($nextFree || $nextHasClass) {
                                break;
                            }

                            $endP = $nextP;
                            foreach ($nextItems as $nit) {
                                $collectedItems[] = $nit;
                            }
                        }

                        $roomRaw = null;
                        $subjectParts = [];

                        foreach ($collectedItems as $cIt) {
                            $txt = $cIt['text'];
                            if (strcasecmp($txt, $foundClass) === 0) continue;
                            if (preg_match('/^(?:LAB|RUANG|R\.)\s*[A-Z0-9\s-]+$/i', $txt)) {
                                $roomRaw = $txt;
                            } else {
                                $cleanTxt = preg_replace($classPattern, '', $txt);
                                $cleanTxt = trim($cleanTxt);
                                if (!empty($cleanTxt)) $subjectParts[] = $cleanTxt;
                            }
                        }

                        $subjectRaw = trim(implode(' ', $subjectParts)) ?: 'Pelajaran';

                        for ($sp = $startP; $sp <= $endP; $sp++) {
                            $slots[] = [
                                'day'          => $day,
                                'period'       => $sp,
                                'teacher_raw'  => $teacherName,
                                'teacher_code' => $teacherCode,
                                'subject'      => $subjectRaw,
                                'class_raw'    => $foundClass,
                                'room_raw'     => $roomRaw,
                            ];
                        }

                        $pIdx = $endP + 1;
                    } else {
                        $pIdx++;
                    }
                }
            }
        }

        return $slots;
    }

    public function resolveTeacher(string $raw, ?string $code, Collection $teachers): ?User
    {
        // 1. Check code map
        if ($code && isset(ScheduleImportController::TEACHER_CODE_MAP[$code])) {
            $officialName = ScheduleImportController::TEACHER_CODE_MAP[$code];
            $cleanOfficial = $this->employeeService->normalizeName($officialName);
            $found = $teachers->first(fn($t) => $this->employeeService->normalizeName($t->fullname) === $cleanOfficial);
            if ($found) {
                return $found;
            }
        }

        // 2. Exact match by normalized name (Fuzzy never auto-commits)
        if (!empty($raw)) {
            $cleanRaw = $this->employeeService->normalizeName($raw);
            $exact = $teachers->first(fn($t) => $this->employeeService->normalizeName($t->fullname) === $cleanRaw);
            if ($exact) {
                return $exact;
            }
        }

        return null;
    }

    public function resolveClass(string $raw, string $cycle, Collection $classes): SchoolClass
    {
        return SchoolClass::resolveOrCreate($raw, $cycle);
    }

    public function resolveRoom(?string $raw, Collection &$rooms): ?Room
    {
        if ($raw === null) {
            return null;
        }

        $clean = trim($raw);
        if (empty($clean) || $clean === '-') {
            return null;
        }

        if (preg_match('/^(?:teori|r\.?\s*teori|ruang)\s*(\d+)$/i', $clean, $m)) {
            $clean = 'Ruang ' . $m[1];
        }

        $existing = $rooms->first(fn($r) => strcasecmp($r->room_name, $clean) === 0);
        if ($existing) {
            return $existing;
        }

        $room = Room::firstOrCreate(['room_name' => $clean]);
        $rooms->push($room);
        return $room;
    }

    public function normalizeDay(string $day): string
    {
        $d = strtolower(trim($day));
        return match ($d) {
            'senin', 'monday', 'mon' => 'Senin',
            'selasa', 'tuesday', 'tue' => 'Selasa',
            'rabu', 'wednesday', 'wed' => 'Rabu',
            'kamis', 'thursday', 'thu' => 'Kamis',
            'jumat', 'friday', 'fri' => 'Jumat',
            'sabtu', 'saturday', 'sat' => 'Sabtu',
            default => 'Senin',
        };
    }
}
