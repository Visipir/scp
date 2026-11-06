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
     * Union of class IDs the user is authorized to supervise for kesiswaan / leave requests.
     * Null return means unrestricted (all classes, e.g. Waka or unassigned Counselor).
     */
    public function authorizedClassIds(): ?array
    {
        if ((int) $this->role_id === 1) {
            return null; // Unrestricted (all classes)
        }

        // Legacy Role 3 support
        if ((int) $this->role_id === 3 && !$this->homeroomAssignment()->exists() && !$this->counselorAssignments()->exists()) {
            return $this->class_id !== null ? [(int) $this->class_id] : null;
        }

        $ids = [];
        $homeroom = $this->homeroomAssignment;
        if ($homeroom) {
            $ids[] = (int) $homeroom->class_id;
        }

        foreach ($this->counselorAssignments as $counselor) {
            $ids[] = (int) $counselor->class_id;
        }

        if (empty($ids) && (int) $this->role_id === 3) {
            return null;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Homeroom (Wali Kelas) scope.
     */
    public function scopedClassId(): ?int
    {
        if ($this->homeroomAssignment) {
            return (int) $this->homeroomAssignment->class_id;
        }

        if ((int) $this->role_id === 3) {
            return $this->class_id !== null ? (int) $this->class_id : null;
        }

        return null;
    }
}
