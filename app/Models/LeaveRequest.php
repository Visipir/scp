<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope the query to the leave requests a user is allowed to see.
     * Role 1 and BK (role 3 without class_id) see everything; a Wali Kelas (role 3
     * with class_id) sees only their homeroom class; role 4 sees own/children scope.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ((int) $user->role_id === 3) {
            $classId = $user->scopedClassId();
            if ($classId !== null) {
                $query->whereHas('student', fn ($q) => $q->where('class_id', $classId));
            }
        } elseif ((int) $user->role_id === 4) {
            $childrenIds = $user->children()->pluck('id')->all();
            $query->where(function ($q) use ($user, $childrenIds) {
                $q->where('student_id', $user->id)
                  ->orWhere('applicant_id', $user->id)
                  ->orWhereIn('student_id', $childrenIds);
            });
        }

        return $query;
    }

    /**
     * Whether a single leave request is visible to the given user (see scopeVisibleTo).
     */
    public function isVisibleTo(User $user): bool
    {
        if ((int) $user->role_id === 1) {
            return true;
        }

        if ((int) $user->role_id === 3) {
            $classId = $user->scopedClassId();

            return $classId === null || (int) $this->student?->class_id === $classId;
        }

        if ((int) $user->role_id === 4) {
            return $this->student_id === $user->id
                || $this->applicant_id === $user->id
                || $user->children()->whereKey($this->student_id)->exists();
        }

        return false;
    }
}
