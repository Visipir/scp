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
     * Automatically mark pending swaps whose swap_date has passed as expired.
     */
    public function autoExpirePendingSwaps(): void
    {
        $today = Carbon::today('Asia/Jakarta');

        ScheduleSwap::where('status', 'pending')
            ->whereDate('swap_date', '<', $today)
            ->update(['status' => 'expired']);
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
     * Approve schedule swap request with strict conflict verification inside a DB transaction.
     * Only the target teacher can approve.
     * Swaps WORK even when the schedule is locked.
     */
    public function approveSwap(User $target, ScheduleSwap $swap): ScheduleSwap
    {
        $this->autoExpirePendingSwaps();

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
                abort(422, 'Persetujuan gagal: Jadwal pemohon sudah tidak valid atau berubah kepemilikan.');
            }

            if (! $targetSched || (int) $targetSched->teacher_id !== (int) $swap->target_id) {
                abort(422, 'Persetujuan gagal: Jadwal target sudah tidak valid atau berubah kepemilikan.');
            }

            // Check if any journal was created while request was pending
            if (Journal::whereDate('date', $swap->swap_date)->whereIn('schedule_id', [$reqSched->id, $targetSched->id])->exists()) {
                abort(422, 'Tidak dapat menyetujui pertukaran: salah satu jadwal sudah memiliki jurnal pada tanggal tersebut.');
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
                    abort(422, "Persetujuan gagal: Terdapat bentrok jadwal mengajar untuk guru pemohon ({$swap->requester->fullname}) pada jam {$other->start_time} - {$other->end_time}.");
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
                    abort(422, "Persetujuan gagal: Terdapat bentrok jadwal mengajar untuk guru Anda pada jam {$other->start_time} - {$other->end_time}.");
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
                        abort(422, "Persetujuan gagal: Ruangan {$targetSched->room?->room_name} bentrok dengan jadwal lain pada jam tersebut.");
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
                        abort(422, "Persetujuan gagal: Ruangan {$reqSched->room?->room_name} bentrok dengan jadwal lain pada jam tersebut.");
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
                    abort(422, "Persetujuan gagal: Kelas {$targetSched->schoolClass?->class_name} bentrok dengan jadwal lain pada jam tersebut.");
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
                    abort(422, "Persetujuan gagal: Kelas {$reqSched->schoolClass?->class_name} bentrok dengan jadwal lain pada jam tersebut.");
                }
            }

            $swap->update([
                'status'       => 'approved',
                'responded_at' => now(),
            ]);

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
}
