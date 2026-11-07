<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProtectCredentialsCommand extends Command
{
    protected $signature = 'accounts:protect-credentials
                            {--apply : Set must_change_password=false on protected accounts without altering password}
                            {--dry-run : Explicitly run in dry-run mode (default)}';

    protected $description = 'Ensure must_change_password=false for the 5 protected tester accounts and print sha256(password) fingerprints before/after without touching passwords';

    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');
        $protectedUsernames = User::protectedUsernames();

        $this->info($isApply
            ? 'Running accounts:protect-credentials in APPLY mode (updating ONLY must_change_password=false).'
            : 'Running accounts:protect-credentials in DRY-RUN mode (no DB changes).'
        );

        $beforeUsers = User::whereIn('username', $protectedUsernames)
            ->orderBy('id')
            ->get()
            ->keyBy('username');

        if ($beforeUsers->isEmpty()) {
            $this->warn('No protected accounts found in database.');
            return Command::SUCCESS;
        }

        $beforeSnapshot = [];
        foreach ($protectedUsernames as $uname) {
            $u = $beforeUsers->get($uname);
            if (!$u) {
                $this->warn("Protected account '{$uname}' not found in database.");
                continue;
            }
            $beforeSnapshot[$uname] = [
                'id'              => (int) $u->id,
                'username'        => $u->username,
                'fullname'        => $u->fullname,
                'role_id'         => (int) $u->role_id,
                'must_change'     => (int) ((bool) $u->must_change_password),
                'password_sha256' => hash('sha256', (string) $u->password),
            ];
        }

        $updatedRows = 0;

        if ($isApply) {
            DB::beginTransaction();
            try {
                foreach ($beforeSnapshot as $snap) {
                    if ($snap['must_change'] !== 0) {
                        // Update ONLY the must_change_password column on the target row
                        $affected = DB::table('users')
                            ->where('id', $snap['id'])
                            ->update(['must_change_password' => 0]);
                        $updatedRows += $affected;
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Failed to update must_change_password: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        $afterUsers = User::whereIn('username', $protectedUsernames)
            ->orderBy('id')
            ->get()
            ->keyBy('username');

        $tableRows = [];
        foreach ($beforeSnapshot as $uname => $before) {
            $afterUser = $afterUsers->get($uname);
            $afterMustChange = $isApply
                ? (int) ((bool) $afterUser->must_change_password)
                : 0;
            $afterSha256 = hash('sha256', (string) $afterUser->password);

            $tableRows[] = [
                'id'          => $before['id'],
                'username'    => $before['username'],
                'fullname'    => $before['fullname'],
                'role_id'     => $before['role_id'],
                'mc_before'   => $before['must_change'] ? 'true (1)' : 'false (0)',
                'mc_after'    => $isApply ? ($afterMustChange ? 'true (1)' : 'false (0)') : 'false (0) [preview]',
                'sha_before'  => $before['password_sha256'],
                'sha_after'   => $afterSha256,
                'sha_matched' => $before['password_sha256'] === $afterSha256 ? 'IDENTICAL' : 'MISMATCH',
            ];
        }

        $this->table(
            ['ID', 'Username', 'Full Name', 'Role', 'MustChange (Before)', 'MustChange (After)', 'Password SHA256 (Before)', 'Password SHA256 (After)', 'Hash Check'],
            $tableRows
        );

        if ($isApply) {
            $this->info("Completed APPLY: {$updatedRows} row(s) had must_change_password updated to false (0). All password hashes verified IDENTICAL.");
        } else {
            $pendingCount = count(array_filter($beforeSnapshot, fn ($s) => $s['must_change'] !== 0));
            $this->warn("DRY-RUN completed: {$pendingCount} row(s) currently have must_change_password=1. Use --apply to update.");
        }

        return Command::SUCCESS;
    }
}
