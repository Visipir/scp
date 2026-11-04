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
    public function scopedClassId(): ?int
    {
        if ((int) $this->role_id !== 3) {
            return null;
        }

        return $this->class_id !== null ? (int) $this->class_id : null;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}
