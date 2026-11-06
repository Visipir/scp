<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealAccountsSeeder extends Seeder
{
    /**
     * Run the production accounts seeder.
     * Not environment-gated. Fully idempotent via updateOrCreate.
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

        // Check if CSV exists
        if (!File::exists($csvPath)) {
            $this->command?->warn("RealAccountsSeeder: No CSV found at {$csvPath}. Skipping seeding.");
            return;
        }

        $rows = $this->parseCsv($csvPath);
        $count = 0;

        foreach ($rows as $row) {
            $username = trim($row['username'] ?? '');
            $name     = trim($row['name'] ?? '');
            $rawRole  = trim($row['role'] ?? '3');
            $classId  = trim($row['class_id'] ?? '');
            $email    = trim($row['email'] ?? '');

            if ($username === '' || $name === '') {
                continue;
            }

            $roleId = match (strtolower($rawRole)) {
                '1', 'waka', 'admin', 'waka kurikulum'             => 1,
                '2', 'guru', 'teacher'                             => 2,
                '3', 'kesiswaan', 'bk', 'guru bk', 'wali kelas'    => 3,
                '4', 'siswa', 'student', 'ortu', 'parent'          => 4,
                default                                            => is_numeric($rawRole) ? (int) $rawRole : 3,
            };

            $resolvedClassId = is_numeric($classId) && (int) $classId > 0 ? (int) $classId : null;
            if ($resolvedClassId !== null && !SchoolClass::where('id', $resolvedClassId)->exists()) {
                $resolvedClassId = null;
            }

            User::updateOrCreate(
                ['username' => $username],
                [
                    'fullname'             => $name,
                    'role_id'              => $roleId,
                    'class_id'             => $resolvedClassId,
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
                    $headers = array_map(fn($h) => strtolower(trim($h)), $data);
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
