<?php

namespace Database\Seeders;

use App\Models\SystemConfiguration;
use Illuminate\Database\Seeder;

class SystemConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        SystemConfiguration::firstOrCreate(['id' => 1], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);
    }
}
