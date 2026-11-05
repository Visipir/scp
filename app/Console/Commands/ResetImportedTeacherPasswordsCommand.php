<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetImportedTeacherPasswordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:reset-imported-passwords
                            {--apply : Commit password resets to the database}
                            {--dry-run : Explicitly run in dry-run mode (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset imported teacher accounts to SEED_DEFAULT_PASSWORD and flag must_change_password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');

        $defaultPassword = env('SEED_DEFAULT_PASSWORD');
        if (blank($defaultPassword)) {
            $this->error('Environment variable SEED_DEFAULT_PASSWORD is required. Please set it in .env before running.');
            return Command::FAILURE;
        }

        $this->info($isApply
            ? 'Running in APPLY mode. Passwords will be updated.'
            : 'Running in DRY-RUN mode (default). No database changes will be made.'
        );

        // Exclude dummy teachers ID 2 (guru_andi) and 3 (guru_siti)
        $teachers = User::where('role_id', 2)
            ->whereNotIn('id', [2, 3])
            ->orderBy('id')
            ->get();

        if ($teachers->isEmpty()) {
            $this->info('No imported teacher accounts found.');
            return Command::SUCCESS;
        }

        $tableRows = [];
        foreach ($teachers as $teacher) {
            $tableRows[] = [
                'id'                   => $teacher->id,
                'username'             => $teacher->username,
                'fullname'             => $teacher->fullname,
                'must_change_password' => $teacher->must_change_password ? 'true' : 'false',
                'action'               => $isApply ? 'PASSWORD_RESET' : 'PENDING_RESET',
            ];
        }

        $this->table(
            ['ID', 'Username', 'Full Name', 'Current MustChange', 'Action'],
            $tableRows
        );

        $this->info(sprintf('Total Imported Teachers Found: %d', $teachers->count()));

        if (!$isApply) {
            $this->warn('DRY-RUN completed. Database was NOT modified. Use --apply to execute.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $hashedPassword = Hash::make($defaultPassword);
            $count = 0;

            foreach ($teachers as $teacher) {
                $teacher->password = $hashedPassword;
                $teacher->must_change_password = true;
                $teacher->save();

                // Revoke active tokens
                $teacher->tokens()->delete();
                $count++;
            }

            DB::commit();

            $this->info("Successfully reset passwords and flagged must_change_password for {$count} imported teachers.");
            $this->warn('Notice: Existing API tokens for these teachers were revoked.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to reset teacher passwords: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
