<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Ruangan;
use App\Models\TahunAjaran;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['id_role' => 1, 'nama_role' => 'Waka Kurikulum'],
            ['id_role' => 2, 'nama_role' => 'Guru'],
            ['id_role' => 3, 'nama_role' => 'Tim Kesiswaan'],
            ['id_role' => 4, 'nama_role' => 'Siswa/Ortu'], // Or general user
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['id_role' => $role['id_role']], $role);
        }

        // 2. Admin User (Waka)
        User::firstOrCreate(
            ['username' => 'waka'],
            [
                'password_hash' => Hash::make('password'),
                'nama_lengkap' => 'Bapak Waka Kurikulum',
                'no_hp' => '081234567890',
                'id_role' => 1,
                'is_aktif' => 1,
            ]
        );

        // 3. Guru User
        User::firstOrCreate(
            ['username' => 'guru1'],
            [
                'password_hash' => Hash::make('password'),
                'nama_lengkap' => 'Bapak Guru Satu',
                'no_hp' => '081234567891',
                'id_role' => 2,
                'is_aktif' => 1,
            ]
        );
        
        \App\Models\Guru::firstOrCreate([
            'nip' => '198001012005011001',
            'nama_guru' => 'Bapak Guru Satu'
        ]);

        // 4. Basic Master Data
        TahunAjaran::firstOrCreate(['id_tahun_ajaran' => '2026-1'], ['nama_semester' => 'Ganjil 2026/2027', 'is_aktif' => 1]);
        
        Jurusan::firstOrCreate(['id_jurusan' => 'RPL'], ['nama_jurusan' => 'Rekayasa Perangkat Lunak']);
        
        Kelas::firstOrCreate(['id_kelas' => 'X-RPL-1'], ['tingkat' => 'X', 'id_jurusan' => 'RPL']);
        
        Ruangan::firstOrCreate(['id_ruangan' => 'R-01'], ['nama_ruangan' => 'Ruang Teori 1', 'jenis' => 'Teori']);
        
        \App\Models\MataPelajaran::firstOrCreate(['id_mapel' => 'MP-01'], ['nama_mapel' => 'Pemrograman Web']);
    }
}
