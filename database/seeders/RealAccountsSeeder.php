<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealAccountsSeeder extends Seeder
{
    /**
     * Run the production accounts seeder for Waka & real Tim Kesiswaan accounts only.
     * Never generates 48 homeroom (wali.*) placeholder accounts and is not called in DatabaseSeeder.
     */
    public function run(): void
    {
        $defaultPassword = env('SEED_DEFAULT_PASSWORD');
        if (blank($defaultPassword)) {
            throw new \RuntimeException(
                'Environment variable SEED_DEFAULT_PASSWORD is required to seed production accounts without hardcoded passwords.'
            );
        }

        $seedDir = storage_path('app/seed');
        $csvPath = $seedDir . '/accounts.csv';

        if (!File::exists($seedDir)) {
            File::makeDirectory($seedDir, 0755, true);
        }

        if (!File::exists($csvPath)) {
            $this->command?->info("RealAccountsSeeder: No CSV found at {$csvPath}. Bootstrapping base roles and primary Waka Kurikulum account.");
            \App\Models\Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
            \App\Models\Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
            \App\Models\Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
            \App\Models\Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa/Ortu']);
            \App\Models\SystemConfiguration::firstOrCreate(['id' => 1], [
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
            ]);
            User::firstOrCreate(
                ['username' => 'waka_kurikulum'],
                [
                    'fullname' => 'Pak Budi Kurikulum, M.Pd',
                    'role_id' => 1,
                    'password' => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );
            return;
        }

        $rows = $this->parseCsv($csvPath);
        $count = 0;
        $skippedProtected = [];

        foreach ($rows as $row) {
            $username = trim($row['username'] ?? '');
            $name     = trim($row['name'] ?? '');
            $rawRole  = trim($row['role'] ?? '3');
            $email    = trim($row['email'] ?? '');

            if ($username === '' || $name === '') {
                continue;
            }

            // Never seed wali.* placeholder accounts; homeroom teachers come from real Pegawai + Wali/BK import
            if (Str::startsWith(strtolower($username), 'wali.')) {
                continue;
            }

            // Never overwrite protected accounts (F1)
            if (User::isProtectedUsername($username) && User::where('username', $username)->exists()) {
                $skippedProtected[] = $username;
                $this->command?->warn("RealAccountsSeeder: Skipping protected account '{$username}'.");
                continue;
            }

            $roleId = match (strtolower($rawRole)) {
                '1', 'waka', 'admin', 'waka kurikulum' => 1,
                '3', 'kesiswaan', 'bk', 'guru bk'      => 3,
                default                                => null,
            };

            if ($roleId === null) {
                continue;
            }

            User::updateOrCreate(
                ['username' => $username],
                [
                    'fullname'             => $name,
                    'role_id'              => $roleId,
                    'class_id'             => null,
                    'email'                => $email !== '' ? $email : null,
                    'password'             => Hash::make($defaultPassword),
                    'must_change_password' => true,
                ]
            );

            $count++;
        }

        $this->command?->info("RealAccountsSeeder: successfully seeded {$count} real accounts from accounts.csv.");
    }

    /**
     * Parse CSV into associative array using first row as headers.
     */
    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = null;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim($h)), $data);
                    continue;
                }
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}
