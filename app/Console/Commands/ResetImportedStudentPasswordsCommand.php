<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResetImportedStudentPasswordsCommand extends Command
{
    protected $signature = 'students:reset-imported-passwords
                            {--apply : Commit random temporary password resets and export CSV}
                            {--dry-run : Explicitly run in dry-run mode (default)}';

    protected $description = 'Reset imported student accounts to random temporary passwords, flag must_change_password=true, and export CSV credentials to storage/app/private';

    public function handle(UserService $userService): int
    {
        $isApply = (bool) $this->option('apply');

        $this->info($isApply
            ? 'Running in APPLY mode. Target student passwords will be randomized and exported to storage/app/private/credentials/.'
            : 'Running in DRY-RUN mode (default). No database changes will be made.'
        );

        $students = User::where('role_id', 4)
            ->orderBy('id')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No student accounts found.');
            return Command::SUCCESS;
        }

        $targets = $students->filter(fn (User $s) => (bool) $s->must_change_password === true)->values();
        $skipped = $students->reject(fn (User $s) => (bool) $s->must_change_password === true)->values();

        $this->info(sprintf('Total Student Accounts Found (role_id=4): %d', $students->count()));
        $this->info(sprintf('Target Accounts to Reset (must_change_password=true): %d', $targets->count()));
        $this->info(sprintf('Skipped Accounts (already changed password, must_change_password=false): %d', $skipped->count()));

        foreach ($skipped as $s) {
            $this->line(sprintf(
                '  - SKIP ID=%d | NIS=%s | Name=%s | class_id=%s | reason=must_change_password=false (updated_at=%s)',
                $s->id,
                $s->username,
                $s->fullname,
                $s->class_id ?? 'null',
                $s->updated_at
            ));
        }

        if (!$isApply) {
            $this->warn('DRY-RUN completed. Database was NOT modified. Use --apply after confirmation to execute.');
            return Command::SUCCESS;
        }

        $credentials = [];
        $nowIso = now('Asia/Jakarta')->toIso8601String();

        DB::beginTransaction();
        try {
            foreach ($targets as $student) {
                $tempPassword = Str::random(12);
                $student->password = Hash::make($tempPassword, ['rounds' => 4]);
                $student->must_change_password = true;
                $student->save();
                $student->tokens()->delete();

                $credentials[] = [
                    'id'                 => $student->id,
                    'username'           => $student->username,
                    'fullname'           => $student->fullname,
                    'nip'                => '',
                    'role_id'            => 4,
                    'class_id'           => $student->class_id,
                    'temporary_password' => $tempPassword,
                    'reset_at'           => $nowIso,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to reset student passwords: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $csvPath = $userService->writeCredentialsCsv($credentials, 'student_credentials');
        Storage::disk('local')->put(
            'credentials/student_credentials_latest.csv',
            Storage::disk('local')->get($csvPath)
        );

        $this->info("Successfully randomized passwords for " . count($credentials) . " students (skipped " . $skipped->count() . ").");
        $this->info("Credentials exported to storage/app/private/{$csvPath}");

        return Command::SUCCESS;
    }
}
