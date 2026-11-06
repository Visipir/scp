<?php

namespace App\Console\Commands;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class SetupDemoAccountsCommand extends Command
{
    protected $signature = 'accounts:setup-demo
                            {--csv= : Path to CSV file containing role_key,username,password}
                            {--apply : Commit demo account passwords to the database}';

    protected $description = 'Configure 5 tester demo accounts (1 Waka, 1 Guru, 1 Guru Wali, 1 BK, 1 Siswa/Ortu) using passwords from env or CSV without hardcoding';

    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');
        $csvOption = $this->option('csv');

        $csvConfig = [];
        if (!empty($csvOption)) {
            $resolvedPath = str_starts_with($csvOption, '/') ? $csvOption : storage_path('app/private/' . ltrim($csvOption, '/'));
            if (!File::exists($resolvedPath)) {
                $this->error("CSV file not found at {$resolvedPath}");
                return Command::FAILURE;
            }
            $csvConfig = $this->parseCsv($resolvedPath);
        }

        $defaultEnvPass = env('DEMO_DEFAULT_PASSWORD') ?: env('SEED_DEFAULT_PASSWORD');

        $slots = [
            'waka' => [
                'label'    => 'Waka Kurikulum',
                'username' => $csvConfig['waka']['username'] ?? env('DEMO_WAKA_USERNAME', 'waka_kurikulum'),
                'password' => $csvConfig['waka']['password'] ?? env('DEMO_WAKA_PASSWORD', $defaultEnvPass),
                'resolver' => fn (string $u) => User::where('username', $u)->first() ?? User::where('role_id', 1)->orderBy('id')->first(),
            ],
            'guru' => [
                'label'    => 'Guru (Non-Wali)',
                'username' => $csvConfig['guru']['username'] ?? env('DEMO_GURU_USERNAME'),
                'password' => $csvConfig['guru']['password'] ?? env('DEMO_GURU_PASSWORD', $defaultEnvPass),
                'resolver' => function (?string $u) {
                    if ($u) {
                        $found = User::where('username', $u)->first();
                        if ($found) {
                            return $found;
                        }
                    }
                    $waliIds = HomeroomAssignment::pluck('user_id')->all();
                    return User::where('role_id', 2)->whereNotIn('id', array_merge([2, 3], $waliIds))->orderBy('id')->first()
                        ?? User::where('role_id', 2)->orderBy('id')->first();
                },
            ],
            'wali' => [
                'label'    => 'Guru Wali Kelas',
                'username' => $csvConfig['wali']['username'] ?? env('DEMO_WALI_USERNAME', 'haris-budiawan'),
                'password' => $csvConfig['wali']['password'] ?? env('DEMO_WALI_PASSWORD', $defaultEnvPass),
                'resolver' => function (?string $u) {
                    if ($u) {
                        $found = User::where('username', $u)->first();
                        if ($found) {
                            return $found;
                        }
                    }
                    $waliId = HomeroomAssignment::value('user_id');
                    return $waliId ? User::find($waliId) : User::where('role_id', 2)->orderBy('id')->first();
                },
            ],
            'bk' => [
                'label'    => 'Guru BK',
                'username' => $csvConfig['bk']['username'] ?? env('DEMO_BK_USERNAME', 'sri-wahyuningsih'),
                'password' => $csvConfig['bk']['password'] ?? env('DEMO_BK_PASSWORD', $defaultEnvPass),
                'resolver' => function (?string $u) {
                    if ($u) {
                        $found = User::where('username', $u)->first();
                        if ($found) {
                            return $found;
                        }
                    }
                    $bkId = CounselorAssignment::value('user_id');
                    return $bkId ? User::find($bkId) : User::where('role_id', 3)->orderBy('id')->first();
                },
            ],
            'siswa' => [
                'label'    => 'Siswa / Ortu',
                'username' => $csvConfig['siswa']['username'] ?? env('DEMO_SISWA_USERNAME', '17766'),
                'password' => $csvConfig['siswa']['password'] ?? env('DEMO_SISWA_PASSWORD', $defaultEnvPass),
                'resolver' => fn (?string $u) => ($u ? User::where('username', $u)->first() : null) ?? User::where('role_id', 4)->orderBy('id')->first(),
            ],
        ];

        $rows = [];
        $targets = [];

        foreach ($slots as $key => $slot) {
            if (blank($slot['password'])) {
                $this->error("Missing password for demo slot '{$key}'. Provide via DEMO_*_PASSWORD / DEMO_DEFAULT_PASSWORD env or --csv=.");
                return Command::FAILURE;
            }

            /** @var User|null $user */
            $user = ($slot['resolver'])($slot['username']);
            if (!$user) {
                $this->error("Could not find user for demo slot '{$key}'.");
                return Command::FAILURE;
            }

            $targets[] = [
                'key'      => $key,
                'label'    => $slot['label'],
                'user'     => $user,
                'password' => (string) $slot['password'],
            ];

            $rows[] = [
                $slot['label'],
                $user->id,
                $user->username,
                $user->fullname,
                $isApply ? 'UPDATED' : 'DRY-RUN',
            ];
        }

        $this->table(['Peran Demo', 'ID', 'Username', 'Nama Lengkap', 'Status'], $rows);

        if (!$isApply) {
            $this->warn('DRY-RUN completed. Use --apply to update passwords.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            foreach ($targets as $item) {
                /** @var User $u */
                $u = $item['user'];
                $u->password = Hash::make($item['password']);
                $u->must_change_password = false;
                $u->save();
                $u->tokens()->delete();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to setup demo accounts: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->info('Successfully configured 5 demo tester accounts.');
        return Command::SUCCESS;
    }

    private function parseCsv(string $path): array
    {
        $map = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = null;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim($h)), $data);
                    continue;
                }
                if (count($data) === count($headers)) {
                    $row = array_combine($headers, $data);
                    $key = strtolower(trim($row['role_key'] ?? $row['role'] ?? ''));
                    if ($key !== '') {
                        $map[$key] = [
                            'username' => trim($row['username'] ?? '') ?: null,
                            'password' => trim($row['password'] ?? '') ?: null,
                        ];
                    }
                }
            }
            fclose($handle);
        }
        return $map;
    }
}
