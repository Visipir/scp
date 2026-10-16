<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Staff & Teachers
        $staffUsers = [
            ['role_id' => 1, 'username' => 'waka_kurikulum', 'fullname' => 'waka_kurikulum, M.Pd'],
            ['role_id' => 2, 'username' => 'guru_andi',       'fullname' => 'Pak Andi Pratama, S.Kom'],
            ['role_id' => 2, 'username' => 'guru_siti',       'fullname' => 'Bu Siti Nurhaliza, S.T'],
            ['role_id' => 2, 'username' => 'guru_agus',       'fullname' => 'Pak Agus Santoso, S.Pd'],
            ['role_id' => 3, 'username' => 'tim_kesiswaan',   'fullname' => 'tim_kesiswaan, S.Pd'],
        ];

        foreach ($staffUsers as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                [...$data, 'password' => Hash::make('password')],
            );
        }

        // Get Classes
        $classXiiRpl1 = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $classXiiRpl2 = SchoolClass::where('class_name', 'XII RPL 2')->first();
        $classXiRpl1  = SchoolClass::where('class_name', 'XI RPL 1')->first();
        $classXiTkj1  = SchoolClass::where('class_name', 'XI TKJ 1')->first();

        // 1b. Wali Kelas: tim_kesiswaan supervises the homeroom class of siswa_dina.
        //     guru_bk has no class_id and therefore monitors every class.
        User::where('username', 'tim_kesiswaan')->update(['class_id' => $classXiiRpl1?->id]);
        User::firstOrCreate(
            ['username' => 'guru_bk'],
            [
                'role_id'  => 3,
                'fullname' => 'Bu Wati Guru BK',
                'class_id' => null,
                'password' => Hash::make('password'),
            ]
        );

        // 2. Students for XII RPL 1
        $studentsXiiRpl1 = [
            ['username' => 'siswa_dina',   'fullname' => 'Dina Permata Putri'],
            ['username' => 'siswa_ahmad',  'fullname' => 'Ahmad Fauzi'],
            ['username' => 'siswa_bima',   'fullname' => 'Bima Wicaksono'],
            ['username' => 'siswa_citra',  'fullname' => 'Citra Lestari'],
            ['username' => 'siswa_donny',  'fullname' => 'Donny Kurniawan'],
        ];

        foreach ($studentsXiiRpl1 as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'role_id'   => 4,
                    'class_id'  => $classXiiRpl1?->id,
                    'fullname'  => $data['fullname'],
                    'password'  => Hash::make('password'),
                ]
            );
        }

        // 3. Students for XII RPL 2
        $studentsXiiRpl2 = [
            ['username' => 'siswa_erik',   'fullname' => 'Erik Setiawan'],
            ['username' => 'siswa_fani',   'fullname' => 'Fani Rahmawati'],
            ['username' => 'siswa_gilang', 'fullname' => 'Gilang Ramadhan'],
            ['username' => 'siswa_hana',   'fullname' => 'Hana Fitria'],
        ];

        foreach ($studentsXiiRpl2 as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'role_id'   => 4,
                    'class_id'  => $classXiiRpl2?->id,
                    'fullname'  => $data['fullname'],
                    'password'  => Hash::make('password'),
                ]
            );
        }

        // 4. Students for XI TKJ 1
        $studentsXiTkj1 = [
            ['username' => 'siswa_indra',  'fullname' => 'Indra Lesmana'],
            ['username' => 'siswa_joko',   'fullname' => 'Joko Prasetyo'],
            ['username' => 'siswa_kevin',  'fullname' => 'Kevin Sanjaya'],
        ];

        foreach ($studentsXiTkj1 as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'role_id'   => 4,
                    'class_id'  => $classXiTkj1?->id,
                    'fullname'  => $data['fullname'],
                    'password'  => Hash::make('password'),
                ]
            );
        }

        // 5. Parents and link to students
        $parents = [
            ['username' => 'ortu_dina',  'fullname' => 'Ibu Dina (Ortu Dina)',   'child_username' => 'siswa_dina'],
            ['username' => 'ortu_ahmad', 'fullname' => 'Bapak Ahmad (Ortu Fauzi)', 'child_username' => 'siswa_ahmad'],
            ['username' => 'ortu_erik',  'fullname' => 'Bapak Erik (Ortu Erik)',  'child_username' => 'siswa_erik'],
        ];

        foreach ($parents as $parentData) {
            $childUsername = $parentData['child_username'];
            unset($parentData['child_username']);

            $parent = User::firstOrCreate(
                ['username' => $parentData['username']],
                [
                    'role_id'  => 4,
                    'fullname' => $parentData['fullname'],
                    'password' => Hash::make('password'),
                ]
            );

            $child = User::where('username', $childUsername)->first();
            if ($child && $parent) {
                $child->update(['parent_id' => $parent->id]);
            }
        }
    }
}
