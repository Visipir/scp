<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;


    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isEmailVerified(): bool
    {
        return !empty($this->email) && $this->email_verified_at !== null;
    }

    /**
     * List of usernames whose credentials and identity fields are protected (F1).
     *
     * @return array<int, string>
     */
    public static function protectedUsernames(): array
    {
        return config('protected_accounts.usernames', [
            'waka_kurikulum',
            'haris-budiawan',
            'ryawidyaningtyas',
            'tim_kesiswaan',
            '17766',
        ]);
    }

    /**
     * Check whether a given username is in the protected accounts list.
     */
    public static function isProtectedUsername(?string $username): bool
    {
        if ($username === null || $username === '') {
            return false;
        }

        return in_array(trim($username), self::protectedUsernames(), true);
    }

    /**
     * Check whether this user account is protected from credential/identity mutations.
     */
    public function isProtectedAccount(): bool
    {
        return self::isProtectedUsername($this->username);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Homeroom (Wali Kelas) scope: for Tim Kesiswaan (role 3) this is the class they
     * supervise. A null value means they are Guru BK and see every class.
     * Returns null for every other role.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function homeroomAssignment()
    {
        return $this->hasOne(HomeroomAssignment::class, 'user_id');
    }

    public function counselorAssignments()
    {
        return $this->hasMany(CounselorAssignment::class, 'user_id');
    }

    public function aliases()
    {
        return $this->hasMany(UserNameAlias::class, 'user_id');
    }

    public function isHomeroomTeacher(): bool
    {
        return $this->homeroomAssignment()->exists();
    }

    public function isCounselor(): bool
    {
        return (int) $this->role_id === 3 || $this->counselorAssignments()->exists();
    }

    public function hasKesiswaanAccess(): bool
    {
        if (in_array((int) $this->role_id, [1, 3], true)) {
            return true;
        }

        return $this->homeroomAssignment()->exists() || $this->counselorAssignments()->exists();
    }

    /**
     * Class IDs the teacher (role_id = 2) teaches via active schedules or approved schedule swaps.
     *
     * @return array<int>
     */
    public function taughtClassIds(): array
    {
        if ((int) $this->role_id !== 2) {
            return [];
        }

        $directIds = Schedule::where('teacher_id', $this->id)
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $swapScheduleIds = ScheduleSwap::whereIn('status', ['approved', 'Approved'])
            ->where(function ($q) {
                $q->where('requester_id', $this->id)
                  ->orWhere('target_id', $this->id);
            })
            ->get(['requester_schedule_id', 'target_schedule_id'])
            ->flatMap(fn ($s) => [$s->requester_schedule_id, $s->target_schedule_id])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $swapClassIds = !empty($swapScheduleIds)
            ? Schedule::whereIn('id', $swapScheduleIds)->pluck('class_id')->map(fn ($id) => (int) $id)->all()
            : [];

        return array_values(array_unique(array_merge($directIds, $swapClassIds)));
    }

    /**
     * Union of class IDs the user supervises as Waka / BK / Wali Kelas (for leave requests, user management, class edits).
     * Null return means unrestricted (all classes, e.g. Waka or legacy unassigned Role 3).
     * Empty array [] means no kesiswaan/supervision access.
     */
    public function supervisedClassIds(): ?array
    {
        if ((int) $this->role_id === 1) {
            return null; // Unrestricted (all classes)
        }

        $homeroomClassIds = $this->homeroomAssignment()->pluck('class_id')->map(fn ($id) => (int) $id)->all();
        $counselorClassIds = $this->counselorAssignments()->pluck('class_id')->map(fn ($id) => (int) $id)->all();
        $ids = array_values(array_unique(array_merge($homeroomClassIds, $counselorClassIds)));

        if (!empty($ids)) {
            return $ids;
        }

        // Legacy Role 3 support when no assignment rows exist
        if ((int) $this->role_id === 3) {
            return $this->class_id !== null ? [(int) $this->class_id] : null;
        }

        return [];
    }

    /**
     * Union of class IDs the user is authorized to access for class student lists, attendance recaps, and CSV exports.
     * Includes supervised classes (Waka/BK/Wali) + taught classes (from teacher's schedules).
     * Null return means unrestricted (all classes).
     * Empty array [] means no class access.
     */
    public function authorizedClassIds(): ?array
    {
        $supervised = $this->supervisedClassIds();
        if ($supervised === null) {
            return null;
        }

        $taught = $this->taughtClassIds();

        return array_values(array_unique(array_merge($supervised, $taught)));
    }

    /**
     * Whether the user can access class student lists, attendance recaps, and CSV exports (Waka/BK/Wali or teaching teacher).
     */
    public function hasClassAccess(): bool
    {
        return $this->hasKesiswaanAccess() || !empty($this->taughtClassIds());
    }

    /**
     * Centralized policy check whether the user can access a given class (student list, attendance recap, CSV export).
     */
    public function canAccessClass(int|SchoolClass $class): bool
    {
        if (!$this->hasClassAccess()) {
            return false;
        }

        $classId = $class instanceof SchoolClass ? (int) $class->id : (int) $class;
        $allowed = $this->authorizedClassIds();

        return $allowed === null || in_array($classId, $allowed, true);
    }

    /**
     * Abort with 403 unless the user is authorized to access the given class.
     */
    public function authorizeClassAccess(int|SchoolClass $class): void
    {
        abort_unless($this->canAccessClass($class), 403, 'Akses ditolak.');
    }

    /**
     * Policy check whether the user supervises the given class (Waka, BK, or Wali Kelas of that class).
     */
    public function canSuperviseClass(int|SchoolClass $class): bool
    {
        if (!$this->hasKesiswaanAccess()) {
            return false;
        }

        $classId = $class instanceof SchoolClass ? (int) $class->id : (int) $class;
        $allowed = $this->supervisedClassIds();

        return $allowed === null || in_array($classId, $allowed, true);
    }

    /**
     * Abort with 403 unless the user supervises the given class.
     */
    public function authorizeClassSupervision(int|SchoolClass $class): void
    {
        abort_unless($this->canSuperviseClass($class), 403, 'Akses ditolak.');
    }

    /**
     * Policy check whether the user can export temporary login cards for a class (Waka or Wali Kelas of that class).
     */
    public function canExportClassLoginCards(int|SchoolClass $class): bool
    {
        if ((int) $this->role_id === 1) {
            return true;
        }

        $classId = $class instanceof SchoolClass ? (int) $class->id : (int) $class;

        return $this->scopedClassId() === $classId;
    }

    /**
     * Homeroom (Wali Kelas) scope.
     */
    public function scopedClassId(): ?int
    {
        $homeroomId = $this->homeroomAssignment()->value('class_id');
        if ($homeroomId !== null) {
            return (int) $homeroomId;
        }

        if ((int) $this->role_id === 3) {
            return $this->class_id !== null ? (int) $this->class_id : null;
        }

        return null;
    }
}
