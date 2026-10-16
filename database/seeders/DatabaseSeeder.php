<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SystemConfigurationSeeder::class,
            ClassSeeder::class,
            RoomSeeder::class,
            UserSeeder::class,
            AcademicCalendarSeeder::class,
        ]);
    }
}
