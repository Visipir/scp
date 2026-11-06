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
     * Role 1 sees all; teachers with homeroom/counselor see their assigned classes;
     * legacy BK sees all or assigned class; role 4 sees own/children scope.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ((int) $user->role_id === 1) {
            return $query;
        }

        if ($user->hasKesiswaanAccess()) {
            $classIds = $user->authorizedClassIds();
            if ($classIds !== null) {
                $query->whereHas('student', fn ($q) => $q->whereIn('class_id', $classIds));
            }
            return $query;
        }

        if ((int) $user->role_id === 4) {
            $childrenIds = $user->children()->pluck('id')->all();
            $query->where(function ($q) use ($user, $childrenIds) {
                $q->where('student_id', $user->id)
                  ->orWhere('applicant_id', $user->id)
                  ->orWhereIn('student_id', $childrenIds);
            });
            return $query;
        }

        // Other users (e.g. regular teacher without homeroom/counselor role): cannot view
        return $query->whereRaw('0 = 1');
    }

    /**
     * Whether a single leave request is visible to the given user (see scopeVisibleTo).
     */
    public function isVisibleTo(User $user): bool
    {
        if ((int) $user->role_id === 1) {
            return true;
        }

        if ($user->hasKesiswaanAccess()) {
            $classIds = $user->authorizedClassIds();
            if ($classIds === null) {
                return true;
            }
            return in_array((int) $this->student?->class_id, $classIds, true);
        }

        if ((int) $user->role_id === 4) {
            return $this->student_id === $user->id
                || $this->applicant_id === $user->id
                || $user->children()->whereKey($this->student_id)->exists();
        }

        return false;
    }
}
