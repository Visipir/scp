<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDummyAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:purge-dummy
                            {--apply : Delete eligible dummy accounts from the database}
                            {--dry-run : Explicitly run in dry-run mode (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge seeded dummy accounts that have no database relations (dry-run default)';

    /**
     * Known seeded dummy usernames targeted for cleanup.
     * Waka and BK real accounts are protected and excluded.
     */
    public const TARGET_DUMMY_USERNAMES = [
        // Teachers
        'guru_andi',
        'guru_siti',
        'guru_agus',
        // Students
        'siswa_dina',
        'siswa_ahmad',
        'siswa_bima',
        'siswa_citra',
        'siswa_donny',
        'siswa_erik',
        'siswa_fani',
        'siswa_gilang',
        'siswa_hana',
        'siswa_indra',
        'siswa_joko',
        'siswa_kevin',
        // Parents
        'ortu_dina',
        'ortu_ahmad',
        'ortu_erik',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');

        $this->info($isApply
            ? 'Running in APPLY mode. Eligible dummy accounts will be deleted.'
            : 'Running in DRY-RUN mode (default). No accounts will be deleted.'
        );

        $users = User::whereIn('username', self::TARGET_DUMMY_USERNAMES)->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->info('No seeded dummy accounts found in database.');
            return Command::SUCCESS;
        }

        $tableRows = [];
        $eligibleToDelete = [];
        $blockedCount = 0;

        foreach ($users as $user) {
            $relations = $this->countRelations($user);
            $totalRelations = array_sum($relations);

            if ($totalRelations > 0) {
                $blockedCount++;
                $relDetails = [];
                foreach ($relations as $key => $cnt) {
                    if ($cnt > 0) {
                        $relDetails[] = "{$key}:{$cnt}";
                    }
                }
                $status = 'BLOCKED (has relations)';
                $relationText = implode(', ', $relDetails);
            } else {
                $status = $isApply ? 'DELETED' : 'ELIGIBLE (0 relations)';
                $relationText = 'none';
                $eligibleToDelete[] = $user;
            }

            $tableRows[] = [
                'id'        => $user->id,
                'username'  => $user->username,
                'fullname'  => $user->fullname,
                'role_id'   => $user->role_id,
                'relations' => $relationText,
                'status'    => $status,
            ];
        }

        $this->table(
            ['ID', 'Username', 'Full Name', 'Role ID', 'Relations', 'Status'],
            $tableRows
        );

        $this->info(sprintf(
            'Total Targets Found: %d | Blocked (has relations): %d | Eligible for Purge: %d',
            count($users),
            $blockedCount,
            count($eligibleToDelete)
        ));

        if (!$isApply) {
            $this->warn('DRY-RUN completed. Database was NOT modified. Use --apply to execute.');
            return Command::SUCCESS;
        }

        if (empty($eligibleToDelete)) {
            $this->info('No eligible accounts to delete. All candidates are blocked by active relations.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($eligibleToDelete as $user) {
                $user->tokens()->delete();
                $user->delete();
                $deletedCount++;
            }
            DB::commit();

            $this->info("Successfully purged {$deletedCount} dummy accounts.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to purge accounts: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Count all database relations for safety verification.
     */
    public function countRelations(User $user): array
    {
        return [
            'schedules'      => Schedule::where('teacher_id', $user->id)->count(),
            'journals'       => Journal::where('teacher_id', $user->id)
                                    ->orWhereHas('schedule', fn($q) => $q->where('teacher_id', $user->id))
                                    ->count(),
            'leave_requests' => LeaveRequest::where('student_id', $user->id)
                                    ->orWhere('applicant_id', $user->id)
                                    ->orWhere('verifier_id', $user->id)
                                    ->count(),
            'attendances'    => Attendance::where('student_id', $user->id)->count(),
            'swaps'          => ScheduleSwap::where('requester_id', $user->id)
                                    ->orWhere('target_id', $user->id)
                                    ->count(),
            'children'       => User::where('parent_id', $user->id)->count(),
        ];
    }
}
