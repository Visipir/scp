<?php

namespace App\Services;

use App\Exceptions\ScheduleSwapConflictException;
use App\Models\Journal;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\ScheduleSwapLog;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * Normalize date string or Carbon to startOfDay in Asia/Jakarta timezone.
     */
    public function normalizeDate(Carbon|string $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy()->setTimezone('Asia/Jakarta')->startOfDay();
        }

        $str = trim((string) $date);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str, $matches)) {
            return Carbon::createFromFormat('Y-m-d', $matches[0], 'Asia/Jakarta')->startOfDay();
        }

        return Carbon::parse($str, 'Asia/Jakarta')->startOfDay();
    }

    /**
     * Parse schedule time into Carbon instance.
     */
    private function parseTime(string $raw): Carbon
    {
        $clean = trim($raw);

        return strlen($clean) === 5
            ? Carbon::createFromFormat('H:i', $clean, 'Asia/Jakarta')
            : Carbon::createFromFormat('H:i:s', $clean, 'Asia/Jakarta');
    }

    /**
     * Build snapshot array for schedule swap log.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(ScheduleSwap $swap): array
    {
        if (! $swap->relationLoaded('requester') || ! $swap->relationLoaded('target')) {
            $swap->loadMissing([
                'requester',
                'target',
                'requesterSchedule.schoolClass',
                'requesterSchedule.room',
                'targetSchedule.schoolClass',
                'targetSchedule.room',
            ]);
        }

        $reqSched = $swap->requesterSchedule;
        $tarSched = $swap->targetSchedule;

        return [
            'swap_date'          => Carbon::parse($swap->swap_date)->toDateString(),
            'cycle'              => $swap->cycle,
            'requester'          => [
                'id'   => (int) $swap->requester_id,
                'name' => $swap->requester?->fullname ?? 'Guru #' . $swap->requester_id,
            ],
            'target'             => [
                'id'   => (int) $swap->target_id,
                'name' => $swap->target?->fullname ?? 'Guru #' . $swap->target_id,
            ],
            'requester_schedule' => [
                'id'      => $reqSched?->id,
                'subject' => $reqSched?->subject,
                'class'   => $reqSched?->schoolClass?->class_name,
                'room'    => $reqSched?->room?->room_name,
                'day'     => $reqSched?->day_of_week,
                'start'   => $reqSched?->start_time ? substr($reqSched->start_time, 0, 5) : null,
                'end'     => $reqSched?->end_time ? substr($reqSched->end_time, 0, 5) : null,
            ],
            'target_schedule'    => [
                'id'      => $tarSched?->id,
                'subject' => $tarSched?->subject,
                'class'   => $tarSched?->schoolClass?->class_name,
                'room'    => $tarSched?->room?->room_name,
                'day'     => $tarSched?->day_of_week,
                'start'   => $tarSched?->start_time ? substr($tarSched->start_time, 0, 5) : null,
                'end'     => $tarSched?->end_time ? substr($tarSched->end_time, 0, 5) : null,
            ],
            'note'               => $swap->note,
        ];
    }

    /**
     * Append an immutable log row for the swap.
     */
    public function logEvent(
        ScheduleSwap $swap,
        string $event,
        ?int $actorId,
        string $actorRole = 'guru',
        ?string $message = null,
        ?array $snapshot = null
    ): ScheduleSwapLog {
        return ScheduleSwapLog::create([
            'swap_id'    => $swap->id,
            'event'      => $event,
            'actor_id'   => $actorId,
            'actor_role' => $actorRole,
            'message'    => $message,
            'snapshot'   => $snapshot ?? $this->buildSnapshot($swap),
            'created_at' => now(),
        ]);
    }

    /**
     * Automatically mark pending swaps whose swap_date has passed as expired and log the event.
     */
    public function autoExpirePendingSwaps(): void
    {
        $today = Carbon::today('Asia/Jakarta');

        $expiredSwaps = ScheduleSwap::where('status', 'pending')
            ->whereDate('swap_date', '<', $today)
            ->get();

        foreach ($expiredSwaps as $swap) {
            DB::transaction(function () use ($swap) {
                $swap->update(['status' => 'expired']);
                $this->logEvent(
                    $swap,
                    'expired',
                    null,
                    'system',
                    'Kedaluwarsa otomatis karena tanggal pelaksanaan telah terlewati.'
                );
            });
        }
    }

    /**
     * Get candidate teachers and their schedules that can be swapped.
     * Must be in the same day and active cycle, with no existing journals or pending/approved swaps.
     * Swaps WORK even when the schedule is locked.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCandidates(User $requester, int $scheduleId, string $date): array
    {
        $this->autoExpirePendingSwaps();

        $cDate = $this->normalizeDate($date);

        if ($cDate->lt(Carbon::today('Asia/Jakarta'))) {
            abort(422, 'Tanggal pertukaran tidak boleh tanggal lampau.');
        }

        $dayName = $this->indonesianDay($cDate);
        if (! $dayName) {
            abort(422, 'Tanggal pertukaran jatuh pada hari yang tidak valid untuk KBM (misal hari Minggu).');
        }

        $config = SystemConfiguration::first();
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

        // Check if requester schedule is already involved in a swap on that date
        $alreadySwapped = ScheduleSwap::whereDate('swap_date', $cDate->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($reqSchedule) {
                $q->where('requester_schedule_id', $reqSchedule->id)
                  ->orWhere('target_schedule_id', $reqSchedule->id);
            })
            ->exists();

        if ($alreadySwapped) {
            abort(422, 'Jadwal Anda sudah memiliki permintaan pertukaran (pending atau disetujui) pada tanggal tersebut.');
        }

        // Find candidate teachers (role_id 2, not self) having schedules on same day & cycle
        $teachers = User::where('role_id', 2)
            ->where('id', '!=', $requester->id)
            ->orderBy('fullname')
            ->get();

        $candidates = [];

        // Exclude schedules that already have journals on swap date
        $filledScheduleIds = Journal::whereDate('date', $cDate->toDateString())
            ->pluck('schedule_id')
            ->toArray();

        // Exclude schedules that already have pending or approved swaps on that date
        $busyScheduleIds = ScheduleSwap::whereDate('swap_date', $cDate->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->flatMap(fn ($s) => [$s->requester_schedule_id, $s->target_schedule_id])
            ->unique()
            ->toArray();

        $excludedScheduleIds = array_unique(array_merge($filledScheduleIds, $busyScheduleIds));

        foreach ($teachers as $teacher) {
            $schedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $teacher->id)
                ->where('day_of_week', $dayName)
                ->where('cycle_type', $activeCycle)
                ->whereNotIn('id', $excludedScheduleIds)
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
     * Swaps WORK even when the schedule is locked.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSwapRequest(User $requester, array $data): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        if ((int) $requester->role_id !== 2) {
            abort(403, 'Hanya guru yang dapat mengajukan pertukaran jadwal.');
        }

        $targetId = (int) $data['target_id'];
        if ($targetId === (int) $requester->id) {
            abort(422, 'Tidak dapat menukar jadwal dengan diri sendiri.');
        }

        User::where('id', $targetId)->where('role_id', 2)->firstOrFail();

        $reqSchedule = Schedule::where('id', $data['requester_schedule_id'])->firstOrFail();
        if ((int) $reqSchedule->teacher_id !== (int) $requester->id) {
            abort(403, 'Jadwal pemohon bukan milik Anda.');
        }

        $targetSchedule = Schedule::where('id', $data['target_schedule_id'])->firstOrFail();
        if ((int) $targetSchedule->teacher_id !== $targetId) {
            abort(422, 'Jadwal target tidak sesuai dengan guru target.');
        }

        $config = SystemConfiguration::first();
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';
        if ($reqSchedule->cycle_type !== $activeCycle || $targetSchedule->cycle_type !== $activeCycle) {
            abort(422, "Jadwal yang ditukar harus berada pada putaran aktif saat ini ({$activeCycle}).");
        }

        $cDate = $this->normalizeDate($data['swap_date']);
        if ($cDate->lt(Carbon::today('Asia/Jakarta'))) {
            abort(422, 'Tanggal pertukaran tidak boleh tanggal lampau.');
        }

        $swapDate = $cDate->toDateString();
        $dayName = $this->indonesianDay($cDate);
        if (! $dayName) {
            abort(422, 'Tanggal pertukaran jatuh pada hari yang tidak valid untuk KBM (misal hari Minggu).');
        }

        if ($reqSchedule->day_of_week !== $dayName || $targetSchedule->day_of_week !== $dayName) {
            abort(422, "Hari jadwal yang ditukar ({$reqSchedule->day_of_week}) harus sesuai dengan tanggal berlakunya ({$dayName}).");
        }

        // Rule 8: If either schedule already has a journal on swap_date, reject
        if (Journal::whereDate('date', $swapDate)->whereIn('schedule_id', [$reqSchedule->id, $targetSchedule->id])->exists()) {
            abort(422, 'Tidak dapat mengajukan pertukaran: salah satu jadwal sudah memiliki jurnal pada tanggal tersebut.');
        }

        // Rule 7: Disallow duplicate or overlapping pending/approved swaps for either schedule on swap_date
        $existingSwap = ScheduleSwap::whereDate('swap_date', $swapDate)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($reqSchedule, $targetSchedule) {
                $q->whereIn('requester_schedule_id', [$reqSchedule->id, $targetSchedule->id])
                  ->orWhereIn('target_schedule_id', [$reqSchedule->id, $targetSchedule->id]);
            })
            ->exists();

        if ($existingSwap) {
            abort(422, 'Sudah ada permintaan tukar jadwal yang masih berstatus pending atau telah disetujui untuk jadwal dan tanggal tersebut.');
        }

        return DB::transaction(function () use ($requester, $targetId, $reqSchedule, $targetSchedule, $activeCycle, $swapDate, $data) {
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

            $this->logEvent(
                $swap,
                'requested',
                $requester->id,
                'guru',
                'Permintaan tukar jadwal diajukan.'
            );

            return $swap->load(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
        });
    }

    /**
     * Approve schedule swap request with strict conflict verification inside a DB transaction.
     * Only the target teacher can approve.
     * Swaps WORK even when the schedule is locked.
     */
    public function approveSwap(User $target, ScheduleSwap $swap): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        try {
            return DB::transaction(function () use ($target, $swap) {
                // Lock swap record for update
                $swap = ScheduleSwap::where('id', $swap->id)->lockForUpdate()->firstOrFail();

                if ((int) $swap->target_id !== (int) $target->id) {
                    abort(403, 'Akses ditolak. Anda bukan guru target untuk permintaan pertukaran ini.');
                }

                if ($swap->status !== 'pending') {
                    abort(422, 'Permintaan pertukaran ini sudah tidak berstatus pending.');
                }

                $swapDateNorm = $this->normalizeDate($swap->swap_date);
                if ($swapDateNorm->lt(Carbon::today('Asia/Jakarta'))) {
                    $swap->update(['status' => 'expired']);
                    $this->logEvent($swap, 'expired', null, 'system', 'Kedaluwarsa otomatis karena tanggal pelaksanaan telah terlewati.');
                    abort(422, 'Permintaan pertukaran sudah kedaluwarsa karena tanggal telah lewat.');
                }

                // Lock and re-validate schedules
                $reqSched = Schedule::with(['schoolClass', 'room'])
                    ->where('id', $swap->requester_schedule_id)
                    ->lockForUpdate()
                    ->first();

                $targetSched = Schedule::with(['schoolClass', 'room'])
                    ->where('id', $swap->target_schedule_id)
                    ->lockForUpdate()
                    ->first();

                if (! $reqSched || (int) $reqSched->teacher_id !== (int) $swap->requester_id) {
                    throw new ScheduleSwapConflictException('Persetujuan gagal: Jadwal pemohon sudah tidak valid atau berubah kepemilikan.');
                }

                if (! $targetSched || (int) $targetSched->teacher_id !== (int) $swap->target_id) {
                    throw new ScheduleSwapConflictException('Persetujuan gagal: Jadwal target sudah tidak valid atau berubah kepemilikan.');
                }

                // Check if any journal was created while request was pending
                if (Journal::whereDate('date', $swap->swap_date)->whereIn('schedule_id', [$reqSched->id, $targetSched->id])->exists()) {
                    throw new ScheduleSwapConflictException('Tidak dapat menyetujui pertukaran: salah satu jadwal sudah memiliki jurnal pada tanggal tersebut.');
                }

                $startReq = $this->parseTime($reqSched->start_time);
                $endReq = $this->parseTime($reqSched->end_time);

                $startTarget = $this->parseTime($targetSched->start_time);
                $endTarget = $this->parseTime($targetSched->end_time);

                $dayName = $reqSched->day_of_week;
                $cycle = $swap->cycle;

                // 1. Conflict Check: Requester (A) taking Target Slot [startTarget, endTarget]
                $reqOtherSchedules = Schedule::where('teacher_id', $swap->requester_id)
                    ->where('day_of_week', $dayName)
                    ->where('cycle_type', $cycle)
                    ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                    ->get();

                foreach ($reqOtherSchedules as $other) {
                    $sOther = $this->parseTime($other->start_time);
                    $eOther = $this->parseTime($other->end_time);

                    if ($startTarget->lt($eOther) && $endTarget->gt($sOther)) {
                        throw new ScheduleSwapConflictException("Persetujuan gagal: Terdapat bentrok jadwal mengajar untuk guru pemohon ({$swap->requester->fullname}) pada jam {$other->start_time} - {$other->end_time}.");
                    }
                }

                // 2. Conflict Check: Target (B) taking Requester Slot [startReq, endReq]
                $targetOtherSchedules = Schedule::where('teacher_id', $swap->target_id)
                    ->where('day_of_week', $dayName)
                    ->where('cycle_type', $cycle)
                    ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                    ->get();

                foreach ($targetOtherSchedules as $other) {
                    $sOther = $this->parseTime($other->start_time);
                    $eOther = $this->parseTime($other->end_time);

                    if ($startReq->lt($eOther) && $endReq->gt($sOther)) {
                        throw new ScheduleSwapConflictException("Persetujuan gagal: Terdapat bentrok jadwal mengajar untuk guru Anda pada jam {$other->start_time} - {$other->end_time}.");
                    }
                }

                // 3. Conflict Check: Room conflicts
                if ($targetSched->room_id) {
                    $roomOtherSchedules = Schedule::where('room_id', $targetSched->room_id)
                        ->where('day_of_week', $dayName)
                        ->where('cycle_type', $cycle)
                        ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                        ->get();

                    foreach ($roomOtherSchedules as $other) {
                        $sOther = $this->parseTime($other->start_time);
                        $eOther = $this->parseTime($other->end_time);

                        if ($startTarget->lt($eOther) && $endTarget->gt($sOther)) {
                            throw new ScheduleSwapConflictException("Persetujuan gagal: Ruangan {$targetSched->room?->room_name} bentrok dengan jadwal lain pada jam tersebut.");
                        }
                    }
                }

                if ($reqSched->room_id) {
                    $roomOtherSchedules = Schedule::where('room_id', $reqSched->room_id)
                        ->where('day_of_week', $dayName)
                        ->where('cycle_type', $cycle)
                        ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                        ->get();

                    foreach ($roomOtherSchedules as $other) {
                        $sOther = $this->parseTime($other->start_time);
                        $eOther = $this->parseTime($other->end_time);

                        if ($startReq->lt($eOther) && $endReq->gt($sOther)) {
                            throw new ScheduleSwapConflictException("Persetujuan gagal: Ruangan {$reqSched->room?->room_name} bentrok dengan jadwal lain pada jam tersebut.");
                        }
                    }
                }

                // 4. Conflict Check: Class conflicts
                $classTargetOther = Schedule::where('class_id', $targetSched->class_id)
                    ->where('day_of_week', $dayName)
                    ->where('cycle_type', $cycle)
                    ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                    ->get();

                foreach ($classTargetOther as $other) {
                    $sOther = $this->parseTime($other->start_time);
                    $eOther = $this->parseTime($other->end_time);

                    if ($startTarget->lt($eOther) && $endTarget->gt($sOther)) {
                        throw new ScheduleSwapConflictException("Persetujuan gagal: Kelas {$targetSched->schoolClass?->class_name} bentrok dengan jadwal lain pada jam tersebut.");
                    }
                }

                $classReqOther = Schedule::where('class_id', $reqSched->class_id)
                    ->where('day_of_week', $dayName)
                    ->where('cycle_type', $cycle)
                    ->whereNotIn('id', [$reqSched->id, $targetSched->id])
                    ->get();

                foreach ($classReqOther as $other) {
                    $sOther = $this->parseTime($other->start_time);
                    $eOther = $this->parseTime($other->end_time);

                    if ($startReq->lt($eOther) && $endReq->gt($sOther)) {
                        throw new ScheduleSwapConflictException("Persetujuan gagal: Kelas {$reqSched->schoolClass?->class_name} bentrok dengan jadwal lain pada jam tersebut.");
                    }
                }

                $swap->update([
                    'status'       => 'approved',
                    'responded_at' => now(),
                ]);

                $this->logEvent(
                    $swap,
                    'approved',
                    $target->id,
                    'guru',
                    'Pertukaran jadwal disetujui.'
                );

                $dateFormatted = Carbon::parse($swap->swap_date)->format('Y-m-d');

                Notification::create([
                    'user_id' => $swap->requester_id,
                    'type'    => 'schedule_swap_approved',
                    'data'    => [
                        'title'     => 'Pertukaran Jadwal Disetujui',
                        'message'   => "Permintaan tukar jadwal Anda dengan {$swap->target->fullname} untuk tanggal {$dateFormatted} telah disetujui.",
                        'swap_id'   => (int) $swap->id,
                        'swap_date' => $dateFormatted,
                    ],
                ]);

                return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
            });
        } catch (ScheduleSwapConflictException $e) {
            $this->logEvent(
                $swap,
                'conflict_blocked',
                $target->id,
                'guru',
                $e->getMessage()
            );

            abort(422, $e->getMessage());
        }
    }

    /**
     * Reject schedule swap request. Only target teacher can reject.
     */
    public function rejectSwap(User $target, ScheduleSwap $swap, ?string $reason = null): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        return DB::transaction(function () use ($target, $swap, $reason) {
            $swap = ScheduleSwap::where('id', $swap->id)->lockForUpdate()->firstOrFail();

            if ((int) $swap->target_id !== (int) $target->id) {
                abort(403, 'Akses ditolak. Anda bukan guru target untuk permintaan pertukaran ini.');
            }

            if ($swap->status !== 'pending') {
                abort(422, 'Permintaan pertukaran ini sudah tidak berstatus pending.');
            }

            $swap->update([
                'status'       => 'rejected',
                'responded_at' => now(),
            ]);

            $this->logEvent(
                $swap,
                'rejected',
                $target->id,
                'guru',
                $reason ?: 'Pertukaran jadwal ditolak.'
            );

            $dateFormatted = Carbon::parse($swap->swap_date)->format('Y-m-d');

            Notification::create([
                'user_id' => $swap->requester_id,
                'type'    => 'schedule_swap_rejected',
                'data'    => [
                    'title'     => 'Pertukaran Jadwal Ditolak',
                    'message'   => "{$target->fullname} menolak permintaan tukar jadwal Anda untuk tanggal {$dateFormatted}.",
                    'swap_id'   => (int) $swap->id,
                    'swap_date' => $dateFormatted,
                    'reason'    => $reason,
                ],
            ]);

            return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
        });
    }

    /**
     * Cancel pending schedule swap request by requester.
     */
    public function cancelSwap(User $requester, ScheduleSwap $swap): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

        return DB::transaction(function () use ($requester, $swap) {
            $swap = ScheduleSwap::where('id', $swap->id)->lockForUpdate()->firstOrFail();

            if ((int) $swap->requester_id !== (int) $requester->id) {
                abort(403, 'Akses ditolak. Anda bukan pemohon pertukaran ini.');
            }

            if ($swap->status !== 'pending') {
                abort(422, 'Hanya permintaan berstatus pending yang dapat dibatalkan.');
            }

            $swap->update([
                'status'       => 'cancelled',
                'responded_at' => now(),
            ]);

            $this->logEvent(
                $swap,
                'cancelled',
                $requester->id,
                'guru',
                'Permintaan tukar jadwal dibatalkan oleh pemohon.'
            );

            $dateFormatted = Carbon::parse($swap->swap_date)->format('Y-m-d');

            Notification::create([
                'user_id' => $swap->target_id,
                'type'    => 'schedule_swap_cancelled',
                'data'    => [
                    'title'     => 'Permintaan Tukar Jadwal Dibatalkan',
                    'message'   => "{$requester->fullname} membatalkan permintaan tukar jadwal untuk tanggal {$dateFormatted}.",
                    'swap_id'   => (int) $swap->id,
                    'swap_date' => $dateFormatted,
                ],
            ]);

            return $swap->fresh(['requester', 'target', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass']);
        });
    }

    /**
     * Single Source of Truth: Get effective schedules for a teacher on a specific date.
     * Incorporates approved schedule_swaps as overrides without modifying master schedules.
     *
     * @return Collection<int, Schedule>
     */
    public function getEffectiveSchedulesForTeacher(int $teacherId, Carbon|string $date, ?string $cycle = null): Collection
    {
        $cDate = $this->normalizeDate($date);
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
                return ((int) $swap->requester_id === (int) $teacherId && (int) $swap->requester_schedule_id === (int) $master->id)
                    || ((int) $swap->target_id === (int) $teacherId && (int) $swap->target_schedule_id === (int) $master->id);
            });

            if (! $swappedOut) {
                $master->is_swapped = false;
                $master->effective_teacher_id = (int) $teacherId;
                $master->swap_id = null;
                $effective->push($master);
            }
        }

        // Check which schedules teacher received as substitute from approved swaps
        foreach ($approvedSwaps as $swap) {
            if ((int) $swap->requester_id === (int) $teacherId && $swap->targetSchedule) {
                // Teacher received target_schedule
                $received = clone $swap->targetSchedule;
                $received->is_swapped = true;
                $received->is_substitute = true;
                $received->effective_teacher_id = (int) $teacherId;
                $received->swap_id = (int) $swap->id;
                $received->partner_teacher = $swap->target;
                $effective->push($received);
            } elseif ((int) $swap->target_id === (int) $teacherId && $swap->requesterSchedule) {
                // Teacher received requester_schedule
                $received = clone $swap->requesterSchedule;
                $received->is_swapped = true;
                $received->is_substitute = true;
                $received->effective_teacher_id = (int) $teacherId;
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
        $dateString = $this->normalizeDate($date)->toDateString();
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

        $effectiveTeacherId = ((int) $swap->requester_schedule_id === (int) $scheduleId)
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
            'allowed'              => ($info['teacher_id'] === (int) $teacherId),
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

    /**
     * Get Waka Swap Report with summary metrics, eager loading, and filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     summary: array{total: int, approved: int, pending: int, rejected_cancelled_expired: int},
     *     swaps: LengthAwarePaginator,
     *     filters: array<string, mixed>
     * }
     */
    public function getWakaSwapReport(array $filters = [], int $perPage = 25): array
    {
        $this->autoExpirePendingSwaps();

        $config = SystemConfiguration::first();
        $defaultCycle = $config?->active_cycle ?? 'Putaran 1';

        // Default date range: current month in Asia/Jakarta
        $now = Carbon::now('Asia/Jakarta');
        $startDate = !empty($filters['start_date'])
            ? $this->normalizeDate($filters['start_date'])->toDateString()
            : $now->copy()->startOfMonth()->toDateString();
        $endDate = !empty($filters['end_date'])
            ? $this->normalizeDate($filters['end_date'])->toDateString()
            : $now->copy()->endOfMonth()->toDateString();

        $status = $filters['status'] ?? 'all';
        $cycle = !empty($filters['cycle']) && $filters['cycle'] !== 'all' ? $filters['cycle'] : null;
        $teacherId = !empty($filters['teacher_id']) ? (int) $filters['teacher_id'] : null;
        $classId = !empty($filters['class_id']) ? (int) $filters['class_id'] : null;
        $search = !empty($filters['search']) ? trim((string) $filters['search']) : null;

        // Base query with eager loading to prevent N+1
        $baseQuery = ScheduleSwap::with([
            'requester',
            'target',
            'requesterSchedule.schoolClass',
            'requesterSchedule.room',
            'targetSchedule.schoolClass',
            'targetSchedule.room',
            'journals.user',
            'logs.actor',
        ])
        ->whereBetween('swap_date', [$startDate, $endDate]);

        if ($cycle) {
            $baseQuery->where('cycle', $cycle);
        }

        if ($teacherId) {
            $baseQuery->where(function ($q) use ($teacherId) {
                $q->where('requester_id', $teacherId)
                  ->orWhere('target_id', $teacherId);
            });
        }

        if ($classId) {
            $baseQuery->where(function ($q) use ($classId) {
                $q->whereHas('requesterSchedule', fn ($s) => $s->where('class_id', $classId))
                  ->orWhereHas('targetSchedule', fn ($s) => $s->where('class_id', $classId));
            });
        }

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('requester', fn ($u) => $u->where('fullname', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))
                  ->orWhereHas('target', fn ($u) => $u->where('fullname', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))
                  ->orWhereHas('requesterSchedule', fn ($s) => $s->where('subject', 'like', "%{$search}%"))
                  ->orWhereHas('targetSchedule', fn ($s) => $s->where('subject', 'like', "%{$search}%"))
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        // Summary strip calculated on the filtered base query (before status filtering)
        $summaryQuery = clone $baseQuery;
        $allMatching = $summaryQuery->get();

        $summary = [
            'total'                      => $allMatching->count(),
            'approved'                   => $allMatching->where('status', 'approved')->count(),
            'pending'                    => $allMatching->where('status', 'pending')->count(),
            'rejected_cancelled_expired' => $allMatching->whereIn('status', ['rejected', 'cancelled', 'expired'])->count(),
        ];

        // Apply status filter for list
        $listQuery = clone $baseQuery;
        if ($status !== 'all' && !empty($status)) {
            $listQuery->where('status', $status);
        }

        $swaps = $listQuery->orderByDesc('swap_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'summary' => $summary,
            'swaps'   => $swaps,
            'filters' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'status'     => $status,
                'cycle'      => $cycle ?? $defaultCycle,
                'teacher_id' => $teacherId,
                'class_id'   => $classId,
                'search'     => $search,
            ],
        ];
    }

    /**
     * Get Teacher Recap aggregation for Waka report.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getWakaTeacherRecap(array $filters = []): Collection
    {
        $this->autoExpirePendingSwaps();

        $now = Carbon::now('Asia/Jakarta');
        $startDate = !empty($filters['start_date'])
            ? $this->normalizeDate($filters['start_date'])->toDateString()
            : $now->copy()->startOfMonth()->toDateString();
        $endDate = !empty($filters['end_date'])
            ? $this->normalizeDate($filters['end_date'])->toDateString()
            : $now->copy()->endOfMonth()->toDateString();

        $swaps = ScheduleSwap::whereBetween('swap_date', [$startDate, $endDate])->get();

        $teachers = User::where('role_id', 2)->orderBy('fullname')->get();

        $recap = $teachers->map(function (User $teacher) use ($swaps) {
            $requested = $swaps->where('requester_id', $teacher->id);
            $received  = $swaps->where('target_id', $teacher->id);

            $approved = $requested->where('status', 'approved')->count() + $received->where('status', 'approved')->count();
            $rejected = $requested->where('status', 'rejected')->count() + $received->where('status', 'rejected')->count();

            $allTeacherSwaps = $requested->concat($received)->sortByDesc('swap_date');
            $lastSwapDate = $allTeacherSwaps->first()?->swap_date?->format('Y-m-d');

            $total = $requested->count() + $received->count();

            return [
                'teacher'         => $teacher,
                'requested_count' => $requested->count(),
                'received_count'  => $received->count(),
                'approved_count'  => $approved,
                'rejected_count'  => $rejected,
                'total_swaps'     => $total,
                'last_swap_date'  => $lastSwapDate,
                'is_top_5'        => false,
            ];
        });

        // Sort by total swaps desc, then approved desc
        $sorted = $recap->sortByDesc('total_swaps')->values()->all();

        // Mark top 5 if total_swaps > 0
        $topCount = 0;
        foreach ($sorted as $index => $item) {
            if ($item['total_swaps'] > 0 && $topCount < 5) {
                $sorted[$index]['is_top_5'] = true;
                $topCount++;
            }
        }

        return collect($sorted);
    }

    /**
     * Get Chronological Activity Logs for Waka report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getWakaSwapLogs(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = ScheduleSwapLog::with([
            'swap.requester',
            'swap.target',
            'swap.requesterSchedule.schoolClass',
            'swap.targetSchedule.schoolClass',
            'actor',
        ]);

        if (!empty($filters['event']) && $filters['event'] !== 'all') {
            $query->where('event', $filters['event']);
        }

        if (!empty($filters['swap_id'])) {
            $query->where('swap_id', (int) $filters['swap_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhereHas('actor', fn ($u) => $u->where('fullname', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get single swap detail with timeline and substitute journal info.
     */
    public function getWakaSwapDetail(int $swapId): ScheduleSwap
    {
        return ScheduleSwap::with([
            'requester',
            'target',
            'requesterSchedule.schoolClass',
            'requesterSchedule.room',
            'targetSchedule.schoolClass',
            'targetSchedule.room',
            'journals.user',
            'logs.actor',
        ])->findOrFail($swapId);
    }

    /**
     * Today's approved swaps for Waka dashboard card.
     *
     * @return array{today_date: string, approved: Collection<int, ScheduleSwap>, today_count: int, pending_count: int}
     */
    public function getTodayApprovedSwapsForDashboard(): array
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $approved = ScheduleSwap::with([
            'requester',
            'target',
            'requesterSchedule.schoolClass',
            'requesterSchedule.room',
            'targetSchedule.schoolClass',
            'targetSchedule.room',
            'journals',
        ])
        ->whereDate('swap_date', $today)
        ->where('status', 'approved')
        ->orderBy('id')
        ->get();

        $pendingCount = ScheduleSwap::where('status', 'pending')
            ->whereDate('swap_date', '>=', $today)
            ->count();

        return [
            'today_date'    => $today,
            'approved'      => $approved,
            'today_count'   => $approved->count(),
            'pending_count' => $pendingCount,
        ];
    }
}
