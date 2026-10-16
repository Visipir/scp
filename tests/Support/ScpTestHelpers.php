<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Shared fixtures for SCP feature tests.
 */
trait ScpTestHelpers
{
    protected function seedRoles(): void
    {
        foreach ([1 => 'Waka Kurikulum', 2 => 'Guru', 3 => 'Tim Kesiswaan', 4 => 'Siswa/Ortu'] as $id => $name) {
            Role::firstOrCreate(['id' => $id], ['role_name' => $name]);
        }
    }

    protected function makeUser(int $roleId, array $attributes = []): User
    {
        return User::create(array_merge([
            'role_id'  => $roleId,
            'username' => 'user_' . uniqid(),
            'fullname' => 'Pengguna ' . uniqid(),
            'password' => Hash::make('password'),
        ], $attributes));
    }

    protected function makeClass(string $name = 'X PPLG 1', string $year = '2026/2027'): SchoolClass
    {
        return SchoolClass::create(['class_name' => $name, 'academic_year' => $year]);
    }

    protected function makeRoom(string $name = 'Lab RPL 1'): Room
    {
        return Room::create(['room_name' => $name]);
    }

    protected function makeSchedule(array $attributes = []): Schedule
    {
        return Schedule::create(array_merge([
            'teacher_id'   => $this->makeUser(2)->id,
            'class_id'     => $this->makeClass()->id,
            'room_id'      => null,
            'subject'      => 'Pemrograman Web',
            'day_of_week'  => 'Senin',
            'start_time'   => '08:00',
            'end_time'     => '09:00',
            'cycle_type'   => 'Putaran 1',
        ], $attributes));
    }

    protected function makeConfig(array $attributes = []): SystemConfiguration
    {
        return SystemConfiguration::create(array_merge([
            'active_cycle'       => 'Putaran 1',
            'is_schedule_locked' => false,
        ], $attributes));
    }
}
