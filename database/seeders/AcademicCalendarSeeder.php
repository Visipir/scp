<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicCalendar;
use Carbon\Carbon;

class AcademicCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // Libur Nasional
            ['title' => 'HUT RI', 'start_date' => '2026-08-17', 'end_date' => '2026-08-17', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Maulid Nabi', 'start_date' => '2026-08-25', 'end_date' => '2026-08-25', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Raya Natal', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Tahun Baru Masehi', 'start_date' => '2027-01-01', 'end_date' => '2027-01-01', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Isra Mikraj', 'start_date' => '2027-01-16', 'end_date' => '2027-01-16', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Tahun Baru Imlek', 'start_date' => '2027-02-06', 'end_date' => '2027-02-06', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Raya Nyepi', 'start_date' => '2027-03-09', 'end_date' => '2027-03-09', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Raya Idul Fitri (H1)', 'start_date' => '2027-03-10', 'end_date' => '2027-03-10', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Buruh', 'start_date' => '2027-05-01', 'end_date' => '2027-05-01', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Kenaikan Isa Almasih', 'start_date' => '2027-05-06', 'end_date' => '2027-05-06', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Raya Idul Adha', 'start_date' => '2027-05-17', 'end_date' => '2027-05-17', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Tahun Baru Islam', 'start_date' => '2027-05-20', 'end_date' => '2027-05-20', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Hari Lahir Pancasila', 'start_date' => '2027-06-01', 'end_date' => '2027-06-01', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            ['title' => 'Tahun Baru Islam (Observed)', 'start_date' => '2027-06-06', 'end_date' => '2027-06-06', 'category' => 'libur_nasional', 'is_kbm_off' => true],
            
            // Libur Bersama
            ['title' => 'Libur Akhir Semester 1', 'start_date' => '2026-07-01', 'end_date' => '2026-07-10', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Semester Ganjil', 'start_date' => '2026-12-21', 'end_date' => '2026-12-31', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Cuti Bersama Natal', 'start_date' => '2026-12-26', 'end_date' => '2026-12-26', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Awal Ramadhan', 'start_date' => '2027-02-08', 'end_date' => '2027-02-09', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Sekitar Idul Fitri (1)', 'start_date' => '2027-03-05', 'end_date' => '2027-03-06', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Sekitar Idul Fitri (2)', 'start_date' => '2027-03-11', 'end_date' => '2027-03-18', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Akhir Tahun (1)', 'start_date' => '2027-06-21', 'end_date' => '2027-06-30', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            ['title' => 'Libur Akhir Tahun (2)', 'start_date' => '2027-07-01', 'end_date' => '2027-07-10', 'category' => 'libur_bersama', 'is_kbm_off' => true],
            
            // Agenda Sekolah
            ['title' => 'MPLS', 'start_date' => '2026-07-13', 'end_date' => '2026-07-17', 'category' => 'agenda_sekolah', 'is_kbm_off' => true],
            ['title' => 'TOEIC', 'start_date' => '2026-07-27', 'end_date' => '2026-07-31', 'category' => 'agenda_sekolah', 'is_kbm_off' => true],
            ['title' => 'PSTS / PSAS', 'start_date' => '2026-09-01', 'end_date' => '2026-09-07', 'category' => 'agenda_sekolah', 'is_kbm_off' => true],
            ['title' => 'UKK', 'start_date' => '2026-11-09', 'end_date' => '2026-11-13', 'category' => 'agenda_sekolah', 'is_kbm_off' => true],
        ];

        foreach ($events as $event) {
            AcademicCalendar::create($event);
        }
    }
}
