<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // 1. Ensure all Roles 1-5 exist with exact IDs
        $roles = [
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => 'Tim Kesiswaan',
            4 => 'Siswa/Ortu',
            5 => 'Superadmin',
        ];

        foreach ($roles as $id => $roleName) {
            if (!DB::table('roles')->where('id', $id)->exists()) {
                DB::table('roles')->insert([
                    'id'         => $id,
                    'role_name'  => $roleName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Ensure default Superadmin User exists in non-testing environments
        $userExists = DB::table('users')->where('username', 'superadmin')->first();
        if (!$userExists) {
            DB::table('users')->insert([
                'role_id'              => 5,
                'username'             => 'superadmin',
                'fullname'             => 'Super Administrator',
                'password'             => Hash::make('password'),
                'must_change_password' => 0,
                'gender'               => 'L',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        } else {
            DB::table('users')->where('id', $userExists->id)->update([
                'role_id'              => 5,
                'must_change_password' => 0,
                'updated_at'           => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('username', 'superadmin')->delete();
        DB::table('roles')->where('id', 5)->delete();
    }
};
