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

        // Generate baseline template if CSV does not exist
        if (!File::exists($csvPath)) {
            $this->generateDefaultCsv($csvPath);
            $this->command?->info("Created default accounts CSV at: {$csvPath}");
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

    /**
     * Generate baseline accounts.csv template containing:
     * - 1 Waka Kurikulum
     * - 1 Guru BK / Tim Kesiswaan
     * - Wali Kelas for every registered SchoolClass
     */
    private function generateDefaultCsv(string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['username', 'name', 'role', 'class_id', 'email']);

        // 1. Waka Kurikulum (Role 1)
        fputcsv($handle, ['waka.kurikulum', 'Waka Kurikulum', '1', '', 'waka@sekolah.sch.id']);

        // 2. Tim Kesiswaan / BK (Role 3, class_id null = all classes)
        fputcsv($handle, ['guru.bk', 'Guru Bimbingan Konseling', '3', '', 'bk@sekolah.sch.id']);

        // 3. Wali Kelas for each class
        $classes = SchoolClass::orderBy('id')->get();
        foreach ($classes as $class) {
            $classSlug = Str::slug($class->class_name, '.');
            $username = "wali.{$classSlug}";
            $name = "Wali Kelas {$class->class_name}";
            $email = "wali.{$classSlug}@sekolah.sch.id";

            fputcsv($handle, [$username, $name, '3', (string) $class->id, $email]);
        }

        fclose($handle);
    }
}
