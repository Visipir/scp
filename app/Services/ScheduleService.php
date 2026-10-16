<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(private ScheduleConflictService $conflicts)
    {
    }

    /**
     * Resolve which teacher a schedule belongs to (Waka may pass teacher_id).
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveTeacherId(User $user, array $data, ?Schedule $schedule = null): int
    {
        if ((int) $user->role_id === 1 && ! empty($data['teacher_id'])) {
            return (int) $data['teacher_id'];
        }

        return $schedule !== null ? (int) $schedule->teacher_id : (int) $user->id;
    }

    /**
     * Abort with 403 unless the user may mutate this schedule.
     */
    public function authorizeTeacher(User $user, Schedule $schedule): void
    {
        if ((int) $user->role_id !== 1 && (int) $schedule->teacher_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when a conflict exists
     */
    public function create(User $user, array $data): Schedule
    {
        $teacherId = $this->resolveTeacherId($user, $data);

        $conflict = $this->conflicts->findConflict($data, $teacherId);
        if ($conflict) {
            throw ValidationException::withMessages($conflict);
        }

        return Schedule::create([...$data, 'teacher_id' => $teacherId]);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when a conflict exists
     */
    public function update(User $user, Schedule $schedule, array $data): Schedule
    {
        $this->authorizeTeacher($user, $schedule);

        $teacherId = $this->resolveTeacherId($user, $data, $schedule);

        $conflict = $this->conflicts->findConflict($data, $teacherId, $schedule->id);
        if ($conflict) {
            throw ValidationException::withMessages($conflict);
        }

        $payload = $data;
        if ((int) $user->role_id === 1 && ! empty($data['teacher_id'])) {
            $payload['teacher_id'] = $teacherId;
        } else {
            unset($payload['teacher_id']);
        }

        $schedule->update($payload);

        return $schedule;
    }
}
