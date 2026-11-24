<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => 'Tim Kesiswaan',
            4 => 'Siswa/Ortu',
            5 => 'Superadmin',
        ];

        foreach ($roles as $id => $role) {
            Role::firstOrCreate(['id' => $id], ['role_name' => $role]);
        }
    }
}
