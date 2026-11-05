<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NormalizeTeacherUsernamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:normalize-usernames
                            {--apply : Apply username updates to the database}
                            {--dry-run : Explicitly run in dry-run mode (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize teacher usernames into clean slugs based on name without degrees';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');

        $this->info($isApply
            ? 'Running in APPLY mode. Changes will be persisted to database.'
            : 'Running in DRY-RUN mode (default). No changes will be made to database.'
        );

        $teachers = User::where('role_id', 2)->orderBy('id')->get();
        if ($teachers->isEmpty()) {
            $this->warn('No teachers found in the database.');
            return Command::SUCCESS;
        }

        // Keep track of all taken usernames to guarantee global uniqueness
        $takenUsernames = User::pluck('username')->all();
        $assignedInRun = [];

        $tableRows = [];
        $updates = [];

        foreach ($teachers as $teacher) {
            $candidateSlug = $this->generateSlug($teacher->fullname);

            // Handle uniqueness: check against existing usernames and newly assigned ones
            $finalSlug = $candidateSlug;
            $counter = 1;

            while (
                in_array($finalSlug, $assignedInRun, true) ||
                (in_array($finalSlug, $takenUsernames, true) && $finalSlug !== $teacher->username)
            ) {
                $counter++;
                $finalSlug = "{$candidateSlug}-{$counter}";
            }

            $assignedInRun[] = $finalSlug;

            $status = ($finalSlug === $teacher->username) ? 'UNCHANGED' : 'RENAME';

            $tableRows[] = [
                'id'           => $teacher->id,
                'old_username' => $teacher->username,
                'fullname'     => $teacher->fullname,
                'new_username' => $finalSlug,
                'status'       => $status,
            ];

            if ($status === 'RENAME') {
                $updates[] = [
                    'user'         => $teacher,
                    'new_username' => $finalSlug,
                ];
            }
        }

        $this->table(
            ['ID', 'Old Username', 'Full Name', 'New Username', 'Status'],
            $tableRows
        );

        $this->info(sprintf(
            'Total Teachers: %d | Renames Pending: %d | Unchanged: %d',
            count($teachers),
            count($updates),
            count($teachers) - count($updates)
        ));

        if (!$isApply) {
            $this->warn('DRY-RUN completed. Database was NOT modified. Use --apply to execute.');
            return Command::SUCCESS;
        }

        if (empty($updates)) {
            $this->info('All teacher usernames are already normalized. Nothing to update.');
            return Command::SUCCESS;
        }

        // 1. Backup users table before mutating
        $backupPath = $this->createUsersBackup();
        $this->info("Backup successfully written to: {$backupPath}");

        // 2. Execute updates in a transaction and revoke old tokens
        DB::beginTransaction();
        try {
            $renamedCount = 0;
            foreach ($updates as $item) {
                /** @var User $user */
                $user = $item['user'];
                $oldUsername = $user->username;
                $newUsername = $item['new_username'];

                $user->username = $newUsername;
                $user->save();

                // Revoke old tokens to invalidate stale authentication state
                $user->tokens()->delete();

                $renamedCount++;
            }
            DB::commit();

            $this->info("Successfully normalized {$renamedCount} teacher usernames.");
            $this->warn('Notice: Active Sanctum API tokens for modified teachers were revoked. Teachers must re-authenticate with their new username.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to update teacher usernames: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Generate clean slug from teacher fullname (stripping degrees and titles).
     */
    public function generateSlug(string $fullname): string
    {
        // 1. Strip everything after the first comma (e.g. ", S.Pd", ", M.Pd")
        $parts = explode(',', $fullname);
        $namePart = trim($parts[0] ?? '');

        // 2. Strip common Indonesian prefixes (Pak, Bu, Drs., Dra., Prof., Dr., etc.)
        $clean = preg_replace(
            '/^(Pak|Bu|Drs\.|Dra\.|Dr\.|Prof\.|Ir\.|H\.|Hj\.)\s+/i',
            '',
            $namePart
        );

        // 3. Fallback if stripping results in empty string
        $clean = trim((string) $clean);
        if ($clean === '') {
            $clean = $fullname;
        }

        // 4. Return standard kebab-case slug
        $slug = Str::slug($clean);

        return $slug !== '' ? $slug : 'guru';
    }

    /**
     * Create JSON backup of users table.
     */
    protected function createUsersBackup(): string
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now('Asia/Jakarta')->format('Ymd_His');
        $filePath = "{$backupDir}/users_table_backup_{$timestamp}.json";

        $data = DB::table('users')->get()->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($filePath, $data);

        return $filePath;
    }
}
