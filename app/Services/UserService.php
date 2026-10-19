<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    /**
     * Get paginated user list with role & scoping filters.
     */
    public function listUsers(User $actor, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::with(['role', 'schoolClass']);

        // Role 3 (Tim Kesiswaan / BK / Wali Kelas) may only view students (role_id 4)
        if ((int) $actor->role_id === 3) {
            $query->where('role_id', 4);
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null) {
                $query->where('class_id', $scopedClassId);
            }
        } elseif (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $perPage = min(max($perPage, 1), 50);

        return $query->orderBy('role_id')
            ->orderBy('fullname')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new user with proper password hashing and must_change_password flag.
     */
    public function createUser(User $actor, array $data): User
    {
        // Role 3 may only create students (role 4)
        if ((int) $actor->role_id === 3) {
            if ((int) ($data['role_id'] ?? 0) !== 4) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk menambahkan akun siswa.');
            }
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null && isset($data['class_id']) && (int) $data['class_id'] !== $scopedClassId) {
                abort(403, 'Akses ditolak. Anda hanya dapat mendaftarkan siswa ke kelas binaan Anda.');
            }
        }

        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = true;

        if (!in_array((int) $data['role_id'], [3, 4], true)) {
            $data['class_id'] = null;
        }

        return User::create($data);
    }

    /**
     * Retrieve single user detail respecting role-3 scoping.
     */
    public function getUser(User $actor, User $target): User
    {
        if ((int) $actor->role_id === 3) {
            if ((int) $target->role_id !== 4) {
                abort(403, 'Akses ditolak. Anda hanya dapat melihat data siswa.');
            }
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null && (int) $target->class_id !== $scopedClassId) {
                abort(403, 'Akses ditolak. Siswa bukan anggota kelas binaan Anda.');
            }
        }

        return $target->load(['role', 'schoolClass', 'children.schoolClass']);
    }

    /**
     * Update user details with validation of role boundaries.
     */
    public function updateUser(User $actor, User $target, array $data): User
    {
        if ((int) $actor->role_id === 3) {
            if ((int) $target->role_id !== 4 || (isset($data['role_id']) && (int) $data['role_id'] !== 4)) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk mengubah data siswa.');
            }
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null) {
                if ((int) $target->class_id !== $scopedClassId) {
                    abort(403, 'Akses ditolak. Siswa bukan anggota kelas binaan Anda.');
                }
                if (isset($data['class_id']) && (int) $data['class_id'] !== $scopedClassId) {
                    abort(403, 'Akses ditolak. Anda tidak dapat memindahkan siswa ke luar kelas binaan Anda.');
                }
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['role_id']) && !in_array((int) $data['role_id'], [3, 4], true)) {
            $data['class_id'] = null;
        }

        $target->update($data);

        return $target->fresh(['role', 'schoolClass']);
    }

    /**
     * Delete user with self-delete guard and relationship integrity checks.
     */
    public function deleteUser(User $actor, User $target): void
    {
        if ((int) $actor->id === (int) $target->id) {
            abort(422, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ((int) $actor->role_id === 3) {
            if ((int) $target->role_id !== 4) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk menghapus akun siswa.');
            }
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null && (int) $target->class_id !== $scopedClassId) {
                abort(403, 'Akses ditolak. Siswa bukan anggota kelas binaan Anda.');
            }
        }

        // Dependency checks
        if (Attendance::where('student_id', $target->id)->exists()) {
            abort(422, 'Tidak dapat menghapus pengguna karena memiliki data riwayat presensi.');
        }

        if (Schedule::where('teacher_id', $target->id)->exists()) {
            abort(422, 'Tidak dapat menghapus pengguna karena memiliki jadwal mengajar aktif.');
        }

        if (Journal::whereHas('schedule', fn ($q) => $q->where('teacher_id', $target->id))->exists()) {
            abort(422, 'Tidak dapat menghapus pengguna karena memiliki catatan jurnal mengajar.');
        }

        $target->delete();
    }

    /**
     * Reset user password to their username with must_change_password flag set.
     */
    public function resetPassword(User $actor, User $target): User
    {
        if ((int) $actor->role_id === 3) {
            if ((int) $target->role_id !== 4) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk me-reset kata sandi siswa.');
            }
            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null && (int) $target->class_id !== $scopedClassId) {
                abort(403, 'Akses ditolak. Siswa bukan anggota kelas binaan Anda.');
            }
        }

        $target->update([
            'password'             => Hash::make($target->username),
            'must_change_password' => true,
        ]);

        return $target;
    }

    /**
     * Check whether an actor is authorized to manage (update, delete, reset-password) a target user.
     */
    public function canManageUser(User $actor, User $target): bool
    {
        $actorRole = (int) $actor->role_id;

        if ($actorRole === 1) {
            return true;
        }

        if ($actorRole === 3) {
            if ((int) $target->role_id !== 4) {
                return false;
            }

            $scopedClassId = $actor->scopedClassId();
            if ($scopedClassId !== null && (int) $target->class_id !== $scopedClassId) {
                return false;
            }

            return true;
        }

        return false;
    }
}
