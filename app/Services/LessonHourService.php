<?php

namespace App\Services;

class LessonHourService
{
    /**
     * Master Data Konfigurasi Blok Jam Pelajaran PUTARAN 2 (Semester Ganjil TP 2026/2027)
     */
    public const PUTARAN_2_PERIODS = [
        'Senin' => [
            1  => ['period' => 1,  'start_time' => '08:00', 'end_time' => '08:35', 'duration' => 35, 'label' => 'Jam 1 (08.00–08.35, 35m)'],
            2  => ['period' => 2,  'start_time' => '08:35', 'end_time' => '09:10', 'duration' => 35, 'label' => 'Jam 2 (08.35–09.10, 35m)'],
            3  => ['period' => 3,  'start_time' => '09:10', 'end_time' => '09:45', 'duration' => 35, 'label' => 'Jam 3 (09.10–09.45, 35m)'],
            4  => ['period' => 4,  'start_time' => '10:00', 'end_time' => '10:35', 'duration' => 35, 'label' => 'Jam 4 (10.00–10.35, 35m)'],
            5  => ['period' => 5,  'start_time' => '10:35', 'end_time' => '11:10', 'duration' => 35, 'label' => 'Jam 5 (10.35–11.10, 35m)'],
            6  => ['period' => 6,  'start_time' => '11:10', 'end_time' => '11:45', 'duration' => 35, 'label' => 'Jam 6 (11.10–11.45, 35m)'],
            7  => ['period' => 7,  'start_time' => '12:45', 'end_time' => '13:25', 'duration' => 40, 'label' => 'Jam 7 (12.45–13.25, 40m)'],
            8  => ['period' => 8,  'start_time' => '13:25', 'end_time' => '14:00', 'duration' => 35, 'label' => 'Jam 8 (13.25–14.00, 35m)'],
            9  => ['period' => 9,  'start_time' => '14:00', 'end_time' => '14:35', 'duration' => 35, 'label' => 'Jam 9 (14.00–14.35, 35m)'],
            10 => ['period' => 10, 'start_time' => '14:35', 'end_time' => '15:10', 'duration' => 35, 'label' => 'Jam 10 (14.35–15.10, 35m)'],
        ],
        'Selasa' => [
            1  => ['period' => 1,  'start_time' => '07:30', 'end_time' => '08:10', 'duration' => 40, 'label' => 'Jam 1 (07.30–08.10, 40m)'],
            2  => ['period' => 2,  'start_time' => '08:10', 'end_time' => '08:50', 'duration' => 40, 'label' => 'Jam 2 (08.10–08.50, 40m)'],
            3  => ['period' => 3,  'start_time' => '08:50', 'end_time' => '09:30', 'duration' => 40, 'label' => 'Jam 3 (08.50–09.30, 40m)'],
            4  => ['period' => 4,  'start_time' => '09:45', 'end_time' => '10:25', 'duration' => 40, 'label' => 'Jam 4 (09.45–10.25, 40m)'],
            5  => ['period' => 5,  'start_time' => '10:25', 'end_time' => '11:05', 'duration' => 40, 'label' => 'Jam 5 (10.25–11.05, 40m)'],
            6  => ['period' => 6,  'start_time' => '11:05', 'end_time' => '11:45', 'duration' => 40, 'label' => 'Jam 6 (11.05–11.45, 40m)'],
            7  => ['period' => 7,  'start_time' => '12:45', 'end_time' => '13:25', 'duration' => 40, 'label' => 'Jam 7 (12.45–13.25, 40m)'],
            8  => ['period' => 8,  'start_time' => '13:25', 'end_time' => '14:00', 'duration' => 35, 'label' => 'Jam 8 (13.25–14.00, 35m)'],
            9  => ['period' => 9,  'start_time' => '14:00', 'end_time' => '14:35', 'duration' => 35, 'label' => 'Jam 9 (14.00–14.35, 35m)'],
            10 => ['period' => 10, 'start_time' => '14:35', 'end_time' => '15:10', 'duration' => 35, 'label' => 'Jam 10 (14.35–15.10, 35m)'],
        ],
        'Rabu' => [
            1  => ['period' => 1,  'start_time' => '07:15', 'end_time' => '07:55', 'duration' => 40, 'label' => 'Jam 1 (07.15–07.55, 40m)'],
            2  => ['period' => 2,  'start_time' => '07:55', 'end_time' => '08:35', 'duration' => 40, 'label' => 'Jam 2 (07.55–08.35, 40m)'],
            3  => ['period' => 3,  'start_time' => '08:35', 'end_time' => '09:15', 'duration' => 40, 'label' => 'Jam 3 (08.35–09.15, 40m)'],
            4  => ['period' => 4,  'start_time' => '09:30', 'end_time' => '10:10', 'duration' => 40, 'label' => 'Jam 4 (09.30–10.10, 40m)'],
            5  => ['period' => 5,  'start_time' => '10:10', 'end_time' => '10:50', 'duration' => 40, 'label' => 'Jam 5 (10.10–10.50, 40m)'],
            6  => ['period' => 6,  'start_time' => '10:50', 'end_time' => '11:30', 'duration' => 40, 'label' => 'Jam 6 (10.50–11.30, 40m)'],
            7  => ['period' => 7,  'start_time' => '12:30', 'end_time' => '13:10', 'duration' => 40, 'label' => 'Jam 7 (12.30–13.10, 40m)'],
            8  => ['period' => 8,  'start_time' => '13:10', 'end_time' => '13:50', 'duration' => 40, 'label' => 'Jam 8 (13.10–13.50, 40m)'],
            9  => ['period' => 9,  'start_time' => '13:50', 'end_time' => '14:30', 'duration' => 40, 'label' => 'Jam 9 (13.50–14.30, 40m)'],
            10 => ['period' => 10, 'start_time' => '14:30', 'end_time' => '15:10', 'duration' => 40, 'label' => 'Jam 10 (14.30–15.10, 40m)'],
        ],
        'Kamis' => [
            1  => ['period' => 1,  'start_time' => '07:15', 'end_time' => '07:55', 'duration' => 40, 'label' => 'Jam 1 (07.15–07.55, 40m)'],
            2  => ['period' => 2,  'start_time' => '07:55', 'end_time' => '08:35', 'duration' => 40, 'label' => 'Jam 2 (07.55–08.35, 40m)'],
            3  => ['period' => 3,  'start_time' => '08:35', 'end_time' => '09:15', 'duration' => 40, 'label' => 'Jam 3 (08.35–09.15, 40m)'],
            4  => ['period' => 4,  'start_time' => '09:30', 'end_time' => '10:10', 'duration' => 40, 'label' => 'Jam 4 (09.30–10.10, 40m)'],
            5  => ['period' => 5,  'start_time' => '10:10', 'end_time' => '10:50', 'duration' => 40, 'label' => 'Jam 5 (10.10–10.50, 40m)'],
            6  => ['period' => 6,  'start_time' => '10:50', 'end_time' => '11:30', 'duration' => 40, 'label' => 'Jam 6 (10.50–11.30, 40m)'],
            7  => ['period' => 7,  'start_time' => '12:30', 'end_time' => '13:10', 'duration' => 40, 'label' => 'Jam 7 (12.30–13.10, 40m)'],
            8  => ['period' => 8,  'start_time' => '13:10', 'end_time' => '13:50', 'duration' => 40, 'label' => 'Jam 8 (13.10–13.50, 40m)'],
            9  => ['period' => 9,  'start_time' => '13:50', 'end_time' => '14:30', 'duration' => 40, 'label' => 'Jam 9 (13.50–14.30, 40m)'],
            10 => ['period' => 10, 'start_time' => '14:30', 'end_time' => '15:10', 'duration' => 40, 'label' => 'Jam 10 (14.30–15.10, 40m)'],
        ],
        'Jumat' => [
            1  => ['period' => 1, 'start_time' => '07:15', 'end_time' => '07:55', 'duration' => 40, 'label' => 'Jam 1 (07.15–07.55, 40m)'],
            2  => ['period' => 2, 'start_time' => '07:55', 'end_time' => '08:35', 'duration' => 40, 'label' => 'Jam 2 (07.55–08.35, 40m)'],
            3  => ['period' => 3, 'start_time' => '08:35', 'end_time' => '09:15', 'duration' => 40, 'label' => 'Jam 3 (08.35–09.15, 40m)'],
            4  => ['period' => 4, 'start_time' => '09:30', 'end_time' => '10:10', 'duration' => 40, 'label' => 'Jam 4 (09.30–10.10, 40m)'],
            5  => ['period' => 5, 'start_time' => '10:10', 'end_time' => '10:50', 'duration' => 40, 'label' => 'Jam 5 (10.10–10.50, 40m)'],
            6  => ['period' => 6, 'start_time' => '10:50', 'end_time' => '11:25', 'duration' => 35, 'label' => 'Jam 6 (10.50–11.25, 35m)'],
            7  => ['period' => 7, 'start_time' => '12:40', 'end_time' => '13:15', 'duration' => 35, 'label' => 'Jam 7 (12.40–13.15, 35m)'],
            8  => ['period' => 8, 'start_time' => '13:15', 'end_time' => '13:50', 'duration' => 35, 'label' => 'Jam 8 (13.15–13.50, 35m)'],
        ],
        'Sabtu' => [
            1  => ['period' => 1,  'start_time' => '07:15', 'end_time' => '07:55', 'duration' => 40, 'label' => 'Jam 1 (07.15–07.55, 40m)'],
            2  => ['period' => 2,  'start_time' => '07:55', 'end_time' => '08:35', 'duration' => 40, 'label' => 'Jam 2 (07.55–08.35, 40m)'],
            3  => ['period' => 3,  'start_time' => '08:35', 'end_time' => '09:15', 'duration' => 40, 'label' => 'Jam 3 (08.35–09.15, 40m)'],
            4  => ['period' => 4,  'start_time' => '09:30', 'end_time' => '10:10', 'duration' => 40, 'label' => 'Jam 4 (09.30–10.10, 40m)'],
            5  => ['period' => 5,  'start_time' => '10:10', 'end_time' => '10:50', 'duration' => 40, 'label' => 'Jam 5 (10.10–10.50, 40m)'],
            6  => ['period' => 6,  'start_time' => '10:50', 'end_time' => '11:30', 'duration' => 40, 'label' => 'Jam 6 (10.50–11.30, 40m)'],
            7  => ['period' => 7,  'start_time' => '12:30', 'end_time' => '13:10', 'duration' => 40, 'label' => 'Jam 7 (12.30–13.10, 40m)'],
            8  => ['period' => 8,  'start_time' => '13:10', 'end_time' => '13:50', 'duration' => 40, 'label' => 'Jam 8 (13.10–13.50, 40m)'],
            9  => ['period' => 9,  'start_time' => '13:50', 'end_time' => '14:30', 'duration' => 40, 'label' => 'Jam 9 (13.50–14.30, 40m)'],
            10 => ['period' => 10, 'start_time' => '14:30', 'end_time' => '15:10', 'duration' => 40, 'label' => 'Jam 10 (14.30–15.10, 40m)'],
        ],
    ];

    /**
     * Waktu Istirahat & Non-KBM PUTARAN 2
     */
    public const PUTARAN_2_BREAKS = [
        'Senin' => [
            ['name' => 'Upacara Bendera', 'start_time' => '07:00', 'end_time' => '08:00', 'duration' => 60, 'is_kbm' => false, 'label' => 'Upacara Bendera (07.00–08.00)'],
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:45', 'end_time' => '10:00', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.45–10.00, 15m)'],
            ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:45', 'end_time' => '12:45', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.45–12.45, 60m)'],
        ],
        'Selasa' => [
            ['name' => 'Literasi', 'start_time' => '07:00', 'end_time' => '07:30', 'duration' => 30, 'is_kbm' => false, 'label' => 'Literasi (07.00–07.30)'],
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:30', 'end_time' => '09:45', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.30–09.45, 15m)'],
            ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:45', 'end_time' => '12:45', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.45–12.45, 60m)'],
        ],
        'Rabu' => [
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:15', 'end_time' => '09:30', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.15–09.30, 15m)'],
            ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:30', 'end_time' => '12:30', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.30–12.30, 60m)'],
        ],
        'Kamis' => [
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:15', 'end_time' => '09:30', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.15–09.30, 15m)'],
            ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:30', 'end_time' => '12:30', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.30–12.30, 60m)'],
        ],
        'Jumat' => [
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:15', 'end_time' => '09:30', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.15–09.30, 15m)'],
            ['name' => 'ISTIRAHAT II / Sholat Jumat', 'start_time' => '11:25', 'end_time' => '12:40', 'duration' => 75, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Sholat Jumat (11.25–12.40, 75m)'],
        ],
        'Sabtu' => [
            ['name' => 'ISTIRAHAT I', 'start_time' => '09:15', 'end_time' => '09:30', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.15–09.30, 15m)'],
            ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:30', 'end_time' => '12:30', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.30–12.30, 60m)'],
        ],
    ];

    /**
     * Konfigurasi Standar PUTARAN 1 (Default / 35 menit)
     */
    public const PUTARAN_1_PERIODS = [
        1  => ['period' => 1,  'start_time' => '08:00', 'end_time' => '08:35', 'duration' => 35, 'label' => 'Jam 1 (08.00–08.35, 35m)'],
        2  => ['period' => 2,  'start_time' => '08:35', 'end_time' => '09:10', 'duration' => 35, 'label' => 'Jam 2 (08.35–09.10, 35m)'],
        3  => ['period' => 3,  'start_time' => '09:10', 'end_time' => '09:45', 'duration' => 35, 'label' => 'Jam 3 (09.10–09.45, 35m)'],
        4  => ['period' => 4,  'start_time' => '10:00', 'end_time' => '10:35', 'duration' => 35, 'label' => 'Jam 4 (10.00–10.35, 35m)'],
        5  => ['period' => 5,  'start_time' => '10:35', 'end_time' => '11:10', 'duration' => 35, 'label' => 'Jam 5 (10.35–11.10, 35m)'],
        6  => ['period' => 6,  'start_time' => '11:10', 'end_time' => '11:45', 'duration' => 35, 'label' => 'Jam 6 (11.10–11.45, 35m)'],
        7  => ['period' => 7,  'start_time' => '12:45', 'end_time' => '13:20', 'duration' => 35, 'label' => 'Jam 7 (12.45–13.20, 35m)'],
        8  => ['period' => 8,  'start_time' => '13:20', 'end_time' => '13:55', 'duration' => 35, 'label' => 'Jam 8 (13.20–13.55, 35m)'],
        9  => ['period' => 9,  'start_time' => '13:55', 'end_time' => '14:30', 'duration' => 35, 'label' => 'Jam 9 (13.55–14.30, 35m)'],
        10 => ['period' => 10, 'start_time' => '14:30', 'end_time' => '15:05', 'duration' => 35, 'label' => 'Jam 10 (14.30–15.05, 35m)'],
        11 => ['period' => 11, 'start_time' => '15:05', 'end_time' => '15:40', 'duration' => 35, 'label' => 'Jam 11 (15.05–15.40, 35m)'],
        12 => ['period' => 12, 'start_time' => '15:40', 'end_time' => '16:15', 'duration' => 35, 'label' => 'Jam 12 (15.40–16.15, 35m)'],
    ];

    public const PUTARAN_1_BREAKS = [
        ['name' => 'ISTIRAHAT I', 'start_time' => '09:45', 'end_time' => '10:00', 'duration' => 15, 'is_kbm' => false, 'label' => 'ISTIRAHAT I (09.45–10.00, 15m)'],
        ['name' => 'ISTIRAHAT II / Ishoma', 'start_time' => '11:45', 'end_time' => '12:45', 'duration' => 60, 'is_kbm' => false, 'label' => 'ISTIRAHAT II / Ishoma (11.45–12.45, 60m)'],
    ];

    public const BREAKS = self::PUTARAN_1_BREAKS;

    /**
     * Dapatkan daftar jam pelajaran (alias untuk getPeriods)
     */
    public static function getAllPeriods(string $day = 'Senin', string $cycle = 'Putaran 1'): array
    {
        return self::getPeriods($day, $cycle);
    }

    /**
     * Dapatkan daftar jam pelajaran sesuai siklus putaran dan hari
     */
    public static function getPeriods(string $day = 'Senin', string $cycle = 'Putaran 1'): array
    {
        if ($cycle === 'Putaran 2') {
            return self::PUTARAN_2_PERIODS[$day] ?? self::PUTARAN_2_PERIODS['Senin'];
        }

        return self::PUTARAN_1_PERIODS;
    }

    /**
     * Dapatkan daftar istirahat & kegiatan non-KBM sesuai siklus dan hari
     */
    public static function getBreaks(string $day = 'Senin', string $cycle = 'Putaran 1'): array
    {
        if ($cycle === 'Putaran 2') {
            return self::PUTARAN_2_BREAKS[$day] ?? [];
        }

        return self::PUTARAN_1_BREAKS;
    }

    /**
     * Dapatkan waktu mulai dari nomor jam pelajaran
     */
    public static function getStartTime(int $period, string $day = 'Senin', string $cycle = 'Putaran 1'): ?string
    {
        $periods = self::getPeriods($day, $cycle);
        return $periods[$period]['start_time'] ?? null;
    }

    /**
     * Dapatkan waktu selesai dari nomor jam pelajaran
     */
    public static function getEndTime(int $period, string $day = 'Senin', string $cycle = 'Putaran 1'): ?string
    {
        $periods = self::getPeriods($day, $cycle);
        return $periods[$period]['end_time'] ?? null;
    }

    /**
     * Dapatkan matriks konfigurasi lengkap untuk frontend (Alpine / JS)
     */
    public static function getAllMatrix(): array
    {
        return [
            'Putaran 1' => [
                'periods' => [
                    'Senin'  => self::PUTARAN_1_PERIODS,
                    'Selasa' => self::PUTARAN_1_PERIODS,
                    'Rabu'   => self::PUTARAN_1_PERIODS,
                    'Kamis'  => self::PUTARAN_1_PERIODS,
                    'Jumat'  => self::PUTARAN_1_PERIODS,
                    'Sabtu'  => self::PUTARAN_1_PERIODS,
                ],
                'breaks' => [
                    'Senin'  => self::PUTARAN_1_BREAKS,
                    'Selasa' => self::PUTARAN_1_BREAKS,
                    'Rabu'   => self::PUTARAN_1_BREAKS,
                    'Kamis'  => self::PUTARAN_1_BREAKS,
                    'Jumat'  => self::PUTARAN_1_BREAKS,
                    'Sabtu'  => self::PUTARAN_1_BREAKS,
                ],
            ],
            'Putaran 2' => [
                'periods' => self::PUTARAN_2_PERIODS,
                'breaks'  => self::PUTARAN_2_BREAKS,
            ],
        ];
    }

    /**
     * Inferensi nomor jam pelajaran (JP) dari waktu jam:menit
     */
    public static function inferPeriodsFromTime(string $startTime, string $endTime, string $day = 'Senin', string $cycle = 'Putaran 1'): array
    {
        $periods = self::getPeriods($day, $cycle);
        $startFormatted = substr($startTime, 0, 5);
        $endFormatted = substr($endTime, 0, 5);

        $startPeriod = null;
        $endPeriod = null;

        foreach ($periods as $num => $p) {
            if ($p['start_time'] === $startFormatted) {
                $startPeriod = $num;
            }
            if ($p['end_time'] === $endFormatted) {
                $endPeriod = $num;
            }
        }

        // Fallback jika waktu tidak persis pas di batas awal
        if ($startPeriod === null) {
            foreach ($periods as $num => $p) {
                if ($startFormatted <= $p['end_time']) {
                    $startPeriod = $num;
                    break;
                }
            }
        }

        // Fallback batas akhir
        if ($endPeriod === null) {
            foreach (array_reverse($periods, true) as $num => $p) {
                if ($endFormatted >= $p['start_time']) {
                    $endPeriod = $num;
                    break;
                }
            }
        }

        return [
            'start_period' => $startPeriod,
            'end_period'   => $endPeriod,
        ];
    }
}
