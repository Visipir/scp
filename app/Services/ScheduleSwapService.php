<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScheduleSwapService
{
    /**
     * Map English day names to Indonesian.
     */
    public function indonesianDay(Carbon $date): ?string
    {
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];

        return $dayMap[$date->format('l')] ?? null;
    }

    /**
     * Parse schedule time into Carbon instance.
     */
    private function parseTime(string $raw): Carbon
    {
        return strlen($raw) === 5
            ? Carbon::createFromFormat('H:i', $raw)
            : Carbon::createFromFormat('H:i:s', $raw);
    }

    /**
     * Automatically mark pending swaps whose swap_date has passed as expired.
     */
    public function autoExpirePendingSwaps(): void
    {
        ScheduleSwap::where('status', 'pending')
            ->whereDate('swap_date', '<', Carbon::today())
            ->update(['status' => 'expired']);
    }

    /**
     * Get candidate teachers and their schedules that can be swapped.
     * Must be in the same day and active cycle, with no existing journals or pending swaps.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCandidates(User $requester, int $scheduleId, string $date): array
    {
        $this->autoExpirePendingSwaps();

        $cDate = Carbon::parse($date);
        $dayName = $this->indonesianDay($cDate);
        if (! $dayName) {
            return [];
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            abort(422, 'Jadwal sedang dikunci oleh Waka Kurikulum. Tidak dapat menukar jadwal.');
        }

        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $reqSchedule = Schedule::where('id', $scheduleId)
            ->where('teacher_id', $requester->id)
            ->firstOrFail();

        if ($reqSchedule->day_of_week !== $dayName) {
            abort(422, "Jadwal yang dipilih ({$reqSchedule->day_of_week}) tidak sesuai dengan hari tanggal pertukaran ({$dayName}).");
        }

        if ($reqSchedule->cycle_type !== $activeCycle) {
            abort(422, "Jadwal harus berada pada putaran aktif ({$activeCycle}).");
        }

        // Check if requester schedule already has a journal on that date
        if (Journal::whereDate('date', $cDate->toDateString())->where('schedule_id', $reqSchedule->id)->exists()) {
            abort(422, 'Jadwal Anda sudah memiliki jurnal terisi pada tanggal tersebut.');
        }

        // Find candidate teachers (role_id 2, not self) having schedules on same day & cycle
        $teachers = User::where('role_id', 2)
            ->where('id', '!=', $requester->id)
            ->orderBy('fullname')
            ->get();

        $candidates = [];

        // Find busy schedule IDs (already have journal or pending swap)
        $filledScheduleIds = Journal::whereDate('date', $cDate->toDateString())
            ->pluck('schedule_id')
            ->toArray();

        $pendingSwapScheduleIds = ScheduleSwap::whereDate('swap_date', $cDate->toDateString())
            ->where('status', 'pending')
            ->get()
            ->flatMap(fn ($s) => [$s->requester_schedule_id, $s->target_schedule_id])
            ->unique()
            ->toArray();

        foreach ($teachers as $teacher) {
            $schedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $teacher->id)
                ->where('day_of_week', $dayName)
                ->where('cycle_type', $activeCycle)
                ->whereNotIn('id', $filledScheduleIds)
                ->whereNotIn('id', $pendingSwapScheduleIds)
                ->orderBy('start_time')
                ->get();

            if ($schedules->isNotEmpty()) {
                $candidates[] = [
                    'teacher_id' => (int) $teacher->id,
                    'fullname'   => $teacher->fullname,
                    'username'   => $teacher->username,
                    'schedules'  => $schedules->map(fn ($s) => [
                        'id'         => (int) $s->id,
                        'subject'    => $s->subject,
                        'class_id'   => (int) $s->class_id,
                        'class_name' => $s->schoolClass?->class_name,
                        'room_id'    => $s->room_id !== null ? (int) $s->room_id : null,
                        'room_name'  => $s->room?->room_name,
                        'start_time' => substr($s->start_time, 0, 5),
                        'end_time'   => substr($s->end_time, 0, 5),
                        'period'     => $s->start_period && $s->end_period ? "{$s->start_period}-{$s->end_period}" : null,
                    ])->values()->all(),
                ];
            }
        }

        return $candidates;
    }

    /**
     * Create a new schedule swap request.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSwapRequest(User $requester, array $data): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        if ((int) $requester->role_id !== 2) {
            abort(403, 'Hanya guru yang dapat mengajukan pertukaran jadwal.');
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            abort(422, 'Jadwal sedang dikunci oleh Waka Kurikulum. Tidak dapat mengajukan pertukaran jadwal.');
        }

        $targetId = (int) $data['target_id'];
        if ($targetId === (int) $requester->id) {
            abort(422, 'Tidak dapat menukar jadwal dengan diri sendiri.');
        }

        $targetUser = User::where('id', $targetId)->where('role_id', 2)->firstOrFail();

        $reqSchedule = Schedule::where('id', $data['requester_schedule_id'])->firstOrFail();
        if ((int) $reqSchedule->teacher_id !== (int) $requester->id) {
            abort(403, 'Jadwal pemohon bukan milik Anda.');
        }

        $targetSchedule = Schedule::where('id', $data['target_schedule_id'])->firstOrFail();
        if ((int) $targetSchedule->teacher_id !== $targetId) {
            abort(422, 'Jadwal target tidak sesuai dengan guru target.');
        }

        $activeCycle = $config?->active_cycle ?? 'Putaran 1';
        if ($reqSchedule->cycle_type !== $activeCycle || $targetSchedule->cycle_type !== $activeCycle) {
            abort(422, "Jadwal yang ditukar harus berada pada putaran aktif saat ini ({$activeCycle}).");
        }

        $swapDate = Carbon::parse($data['swap_date'])->toDateString();
        if (Carbon::parse($swapDate)->lt(Carbon::today())) {
            abort(422, 'Tanggal pertukaran tidak boleh tanggal lampau.');
        }

        $dayName = $this->indonesianDay(Carbon::parse($swapDate));
        if ($reqSchedule->day_of_week !== $dayName || $targetSchedule->day_of_week !== $dayName) {
            abort(422, "Hari jadwal yang ditukar harus sesuai dengan tanggal berlakunya ({$dayName}).");
        }

        // Rule 8: If either schedule already has a journal on swap_date, reject
        if (Journal::whereDate('date', $swapDate)->whereIn('schedule_id', [$reqSchedule->id, $targetSchedule->id])->exists()) {
            abort(422, 'Tidak dapat mengajukan pertukaran: salah satu jadwal sudah memiliki jurnal pada tanggal tersebut.');
        }

        // Rule 7: Disallow double pending requests for the same schedule and date
        $pendingExists = ScheduleSwap::whereDate('swap_date', $swapDate)
            ->where('status', 'pending')
            ->where(function ($q) use ($reqSchedule, $targetSchedule) {
                $q->whereIn('requester_schedule_id', [$reqSchedule->id, $targetSchedule->id])
                  ->orWhereIn('target_schedule_id', [$reqSchedule->id, $targetSchedule->id]);
            })
            ->exists();

        if ($pendingExists) {
            abort(422, 'Sudah ada permintaan tukar jadwal yang masih berstatus pending untuk jadwal dan tanggal tersebut.');
        }

        $swap = ScheduleSwap::create([
            'requester_id'          => $requester->id,
            'target_id'             => $targetId,
            'requester_schedule_id' => $reqSchedule->id,
            'target_schedule_id'    => $targetSchedule->id,
            'cycle'                 => $activeCycle,
            'swap_date'             => $swapDate,
            'status'                => 'pending',
            'note'                  => $data['note'] ?? null,
        ]);

        Notification::create([
            'user_id' => $targetId,
            'type'    => 'schedule_swap_request',
            'data'    => [
                'title'       => 'Permintaan Tukar Jadwal',
                'message'     => "{$requester->fullname} mengajukan pertukaran jadwal untuk tanggal {$swapDate}.",
                'swap_id'     => (int) $swap->id,
                'requester'   => $requester->fullname,
                'swap_date'   => $swapDate,
            ],
        ]);

        return $swap->load(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
    }

    /**
     * Approve schedule swap request with strict conflict verification.
     */
    public function approveSwap(User $target, ScheduleSwap $swap): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        if ((int) $swap->target_id !== (int) $target->id && (int) $target->role_id !== 1) {
            abort(403, 'Akses ditolak. Anda bukan guru target untuk permintaan pertukaran ini.');
        }

        if ($swap->status !== 'pending') {
            abort(422, 'Permintaan pertukaran ini sudah tidak berstatus pending.');
        }

        if (Carbon::parse($swap->swap_date)->lt(Carbon::today())) {
            $swap->update(['status' => 'expired']);
            abort(422, 'Permintaan pertukaran sudah kedaluwarsa karena tanggal telah lewat.');
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            abort(422, 'Jadwal sedang dikunci oleh Waka Kurikulum. Tidak dapat menyetujui pertukaran jadwal.');
        }

        // Rule 8: Check if any journal was created while request was pending
        if (Journal::whereDate('date', $swap->swap_date)->whereIn('schedule_id', [$swap->requester_schedule_id, $swap->target_schedule_id])->exists()) {
            abort(422, 'Tidak dapat menyetujui pertukaran: salah satu jadwal sudah memiliki jurnal pada tanggal tersebut.');
        }

        // Rule 4: Re-check time overlap conflict using: Start_A < End_B AND End_A > Start_B
        $reqSched = $swap->requesterSchedule;
        $targetSched = $swap->targetSchedule;

        $startReq = $this->parseTime($reqSched->start_time);
        $endReq = $this->parseTime($reqSched->end_time);

        $startTarget = $this->parseTime($targetSched->start_time);
        $endTarget = $this->parseTime($targetSched->end_time);

        $dayName = $reqSched->day_of_week;
        $cycle = $swap->cycle;

        // Check if Requester (P) has conflict with target slot [startTarget, endTarget]
        // among P's other effective schedules on that day (excluding reqSched and targetSched)
        $reqOtherSchedules = Schedule::where('teacher_id', $swap->requester_id)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $cycle)
            ->whereNotIn('id', [$reqSched->id, $targetSched->id])
            ->get();

        foreach ($reqOtherSchedules as $other) {
            $sOther = $this->parseTime($other->start_time);
            $eOther = $this->parseTime($other->end_time);

            if ($startTarget->lt($eOther) && $endTarget->gt($sOther)) {
                abort(422, "Persetujuan gagal: Terdapat bentrok jadwal untuk guru pemohon ({$swap->requester->fullname}) pada jam slot target.");
            }
        }

        // Check if Target (T) has conflict with requester slot [startReq, endReq]
        // among T's other effective schedules on that day (excluding reqSched and targetSched)
        $targetOtherSchedules = Schedule::where('teacher_id', $swap->target_id)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $cycle)
            ->whereNotIn('id', [$reqSched->id, $targetSched->id])
            ->get();

        foreach ($targetOtherSchedules as $other) {
            $sOther = $this->parseTime($other->start_time);
            $eOther = $this->parseTime($other->end_time);

            if ($startReq->lt($eOther) && $endReq->gt($sOther)) {
                abort(422, "Persetujuan gagal: Terdapat bentrok jadwal untuk guru Anda pada jam slot pemohon.");
            }
        }

        DB::transaction(function () use ($swap) {
            // Lock schedules for concurrency safety
            Schedule::whereIn('id', [$swap->requester_schedule_id, $swap->target_schedule_id])
                ->lockForUpdate()
                ->get();

            $swap->update([
                'status'       => 'approved',
                'responded_at' => now(),
            ]);

            Notification::create([
                'user_id' => $swap->requester_id,
                'type'    => 'schedule_swap_approved',
                'data'    => [
                    'title'     => 'Pertukaran Jadwal Disetujui',
                    'message'   => "Permintaan tukar jadwal Anda dengan {$swap->target->fullname} untuk tanggal {$swap->swap_date->format('Y-m-d')} telah disetujui.",
                    'swap_id'   => (int) $swap->id,
                    'swap_date' => $swap->swap_date->format('Y-m-d'),
                ],
            ]);
        });

        return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
    }

    /**
     * Reject schedule swap request.
     */
    public function rejectSwap(User $target, ScheduleSwap $swap, ?string $reason = null): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        if ((int) $swap->target_id !== (int) $target->id && (int) $target->role_id !== 1) {
            abort(403, 'Akses ditolak. Anda bukan guru target untuk permintaan pertukaran ini.');
        }

        if ($swap->status !== 'pending') {
            abort(422, 'Permintaan pertukaran ini sudah tidak berstatus pending.');
        }

        $swap->update([
            'status'       => 'rejected',
            'responded_at' => now(),
        ]);

        Notification::create([
            'user_id' => $swap->requester_id,
            'type'    => 'schedule_swap_rejected',
            'data'    => [
                'title'     => 'Pertukaran Jadwal Ditolak',
                'message'   => "{$target->fullname} menolak permintaan tukar jadwal Anda untuk tanggal {$swap->swap_date->format('Y-m-d')}.",
                'swap_id'   => (int) $swap->id,
                'swap_date' => $swap->swap_date->format('Y-m-d'),
                'reason'    => $reason,
            ],
        ]);

        return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
    }

    /**
     * Cancel pending schedule swap request by requester.
     */
    public function cancelSwap(User $requester, ScheduleSwap $swap): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        if ((int) $swap->requester_id !== (int) $requester->id && (int) $requester->role_id !== 1) {
            abort(403, 'Akses ditolak. Anda bukan pemohon pertukaran ini.');
        }

        if ($swap->status !== 'pending') {
            abort(422, 'Hanya permintaan berstatus pending yang dapat dibatalkan.');
        }

        $swap->update([
            'status'       => 'cancelled',
            'responded_at' => now(),
        ]);

        Notification::create([
            'user_id' => $swap->target_id,
            'type'    => 'schedule_swap_cancelled',
            'data'    => [
                'title'     => 'Permintaan Tukar Jadwal Dibatalkan',
                'message'   => "{$requester->fullname} membatalkan permintaan tukar jadwal untuk tanggal {$swap->swap_date->format('Y-m-d')}.",
                'swap_id'   => (int) $swap->id,
                'swap_date' => $swap->swap_date->format('Y-m-d'),
            ],
        ]);

        return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
    }

    /**
     * Single Source of Truth: Get effective schedules for a teacher on a specific date.
     * Incorporates approved schedule_swaps as overrides without modifying master schedules.
     *
     * @return Collection<int, Schedule>
     */
    public function getEffectiveSchedulesForTeacher(int $teacherId, Carbon|string $date, ?string $cycle = null): Collection
    {
        $cDate = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateString = $cDate->toDateString();
        $dayName = $this->indonesianDay($cDate);

        if (! $dayName) {
            return collect(); // Sunday / invalid day
        }

        $config = SystemConfiguration::first();
        $cycle = $cycle ?? ($config?->active_cycle ?? 'Putaran 1');

        // 1. Fetch approved swaps for this date
        $approvedSwaps = ScheduleSwap::with(['requester', 'target', 'requesterSchedule.schoolClass', 'requesterSchedule.room', 'targetSchedule.schoolClass', 'targetSchedule.room'])
            ->whereDate('swap_date', $dateString)
            ->where('status', 'approved')
            ->get();

        // 2. Fetch master schedules of teacher
        $masterSchedules = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $cycle)
            ->get();

        $effective = collect();

        // Check which master schedules were swapped out (given away to someone else)
        foreach ($masterSchedules as $master) {
            $swappedOut = $approvedSwaps->first(function ($swap) use ($master, $teacherId) {
                return ($swap->requester_id === $teacherId && $swap->requester_schedule_id === $master->id)
                    || ($swap->target_id === $teacherId && $swap->target_schedule_id === $master->id);
            });

            if (! $swappedOut) {
                $master->is_swapped = false;
                $master->effective_teacher_id = $teacherId;
                $master->swap_id = null;
                $effective->push($master);
            }
        }

        // Check which schedules teacher received as substitute from approved swaps
        foreach ($approvedSwaps as $swap) {
            if ($swap->requester_id === $teacherId) {
                // Teacher received target_schedule
                $received = clone $swap->targetSchedule;
                $received->is_swapped = true;
                $received->is_substitute = true;
                $received->effective_teacher_id = $teacherId;
                $received->swap_id = (int) $swap->id;
                $received->partner_teacher = $swap->target;
                $effective->push($received);
            } elseif ($swap->target_id === $teacherId) {
                // Teacher received requester_schedule
                $received = clone $swap->requesterSchedule;
                $received->is_swapped = true;
                $received->is_substitute = true;
                $received->effective_teacher_id = $teacherId;
                $received->swap_id = (int) $swap->id;
                $received->partner_teacher = $swap->requester;
                $effective->push($received);
            }
        }

        return $effective->sortBy(fn ($s) => $s->start_time)->values();
    }

    /**
     * Resolve effective teacher for a schedule on a specific date.
     *
     * @return array{teacher_id: int, swap_id: ?int, is_swapped: bool, original_teacher_id: int}
     */
    public function getEffectiveTeacherForSchedule(int $scheduleId, Carbon|string $date): array
    {
        $dateString = is_string($date) ? $date : $date->toDateString();
        $schedule = Schedule::findOrFail($scheduleId);

        $swap = ScheduleSwap::whereDate('swap_date', $dateString)
            ->where('status', 'approved')
            ->where(function ($q) use ($scheduleId) {
                $q->where('requester_schedule_id', $scheduleId)
                  ->orWhere('target_schedule_id', $scheduleId);
            })
            ->first();

        if (! $swap) {
            return [
                'teacher_id'          => (int) $schedule->teacher_id,
                'swap_id'             => null,
                'is_swapped'          => false,
                'original_teacher_id' => (int) $schedule->teacher_id,
            ];
        }

        $effectiveTeacherId = ($swap->requester_schedule_id === $scheduleId)
            ? (int) $swap->target_id
            : (int) $swap->requester_id;

        return [
            'teacher_id'          => $effectiveTeacherId,
            'swap_id'             => (int) $swap->id,
            'is_swapped'          => true,
            'original_teacher_id' => (int) $schedule->teacher_id,
        ];
    }

    /**
     * Validate whether a teacher is authorized to fill the journal for a schedule on a given date.
     *
     * @return array{allowed: bool, swap_id: ?int, effective_teacher_id: int}
     */
    public function canTeacherFillJournal(int $teacherId, int $scheduleId, Carbon|string $date): array
    {
        $info = $this->getEffectiveTeacherForSchedule($scheduleId, $date);

        return [
            'allowed'              => ($info['teacher_id'] === $teacherId),
            'swap_id'              => $info['swap_id'],
            'effective_teacher_id' => $info['teacher_id'],
        ];
    }

    /**
     * List schedule swaps for user inbox / outbox.
     *
     * @return array{inbox: Collection<int, ScheduleSwap>, outbox: Collection<int, ScheduleSwap>, pending_count: int}
     */
    public function listSwapsForUser(User $user): array
    {
        $this->autoExpirePendingSwaps();

        $inbox = ScheduleSwap::with(['requester.schoolClass', 'requesterSchedule.schoolClass', 'requesterSchedule.room', 'targetSchedule.schoolClass', 'targetSchedule.room'])
            ->where('target_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $outbox = ScheduleSwap::with(['target.schoolClass', 'requesterSchedule.schoolClass', 'requesterSchedule.room', 'targetSchedule.schoolClass', 'targetSchedule.room'])
            ->where('requester_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $pendingCount = $inbox->where('status', 'pending')->count();

        return [
            'inbox'         => $inbox,
            'outbox'        => $outbox,
            'pending_count' => $pendingCount,
        ];
    }

    /**
     * List all swaps for Waka monitoring.
     *
     * @return Collection<int, ScheduleSwap>
     */
    public function listAllSwapsForWaka(): Collection
    {
        $this->autoExpirePendingSwaps();

        return ScheduleSwap::with([
            'requester',
            'target',
            'requesterSchedule.schoolClass',
            'requesterSchedule.room',
            'targetSchedule.schoolClass',
            'targetSchedule.room',
        ])
            ->orderByDesc('swap_date')
            ->orderByDesc('id')
            ->get();
    }
}
