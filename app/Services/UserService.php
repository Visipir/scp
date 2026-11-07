<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    /**
     * Get paginated user list with role & centralized authorizedClassIds scoping filters.
     */
    public function listUsers(User $actor, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        if (!$actor->hasKesiswaanAccess()) {
            abort(403, 'Akses ditolak.');
        }

        $query = User::with(['role', 'schoolClass']);

        // Non-Waka kesiswaan actors (BK / Wali Kelas) may only view students (role_id 4) in their supervisedClassIds
        if ((int) $actor->role_id !== 1) {
            $query->where('role_id', 4);
            $allowedClassIds = $actor->supervisedClassIds();
            if ($allowedClassIds !== null) {
                $query->whereIn('class_id', $allowedClassIds);
            }
        } elseif (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['class_id'])) {
            $query->where('class_id', (int) $filters['class_id']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('id', (int) $filters['student_id']);
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
        if (!$actor->hasKesiswaanAccess()) {
            abort(403, 'Akses ditolak.');
        }

        // Non-Waka kesiswaan actors (BK / Wali Kelas) may only create students (role 4) in their supervisedClassIds
        if ((int) $actor->role_id !== 1) {
            if ((int) ($data['role_id'] ?? 0) !== 4) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk menambahkan akun siswa.');
            }
            $allowedClassIds = $actor->supervisedClassIds();
            if ($allowedClassIds !== null && isset($data['class_id']) && !in_array((int) $data['class_id'], $allowedClassIds, true)) {
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
     * Retrieve single user detail respecting centralized supervisedClassIds scoping.
     */
    public function getUser(User $actor, User $target): User
    {
        if (!$this->canManageUser($actor, $target)) {
            abort(403, 'Akses ditolak.');
        }

        return $target->load(['role', 'schoolClass', 'children.schoolClass']);
    }

    /**
     * Update user details with validation of role boundaries.
     */
    public function updateUser(User $actor, User $target, array $data): User
    {
        if (!$this->canManageUser($actor, $target)) {
            abort(403, 'Akses ditolak.');
        }

        if ($target->isProtectedAccount()) {
            // Protected accounts cannot have their username, password, fullname, role_id, or must_change_password modified
            unset($data['username'], $data['password'], $data['fullname'], $data['role_id'], $data['must_change_password']);
        }

        if ((int) $actor->role_id !== 1) {
            if (isset($data['role_id']) && (int) $data['role_id'] !== 4) {
                abort(403, 'Akses ditolak. Anda hanya memiliki wewenang untuk mengubah data siswa.');
            }
            $allowedClassIds = $actor->supervisedClassIds();
            if ($allowedClassIds !== null && isset($data['class_id']) && !in_array((int) $data['class_id'], $allowedClassIds, true)) {
                abort(403, 'Akses ditolak. Anda tidak dapat memindahkan siswa ke luar kelas binaan Anda.');
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

        // Reset verification status if email is changed or cleared by manager
        if (array_key_exists('email', $data)) {
            $normalizedNew = $data['email'] ? strtolower(trim($data['email'])) : null;
            $normalizedOld = $target->email ? strtolower(trim($target->email)) : null;
            if ($normalizedNew !== $normalizedOld) {
                $data['email'] = $normalizedNew;
                $data['email_verified_at'] = null;
            }
        }

        $target->update($data);

        return $target->fresh(['role', 'schoolClass']);
    }

    /**
     * Delete user with self-delete guard and relationship integrity checks.
     */
    public function deleteUser(User $actor, User $target): void
    {
        if ($target->isProtectedAccount()) {
            abort(422, "Akun {$target->username} adalah akun terlindung penguji dan tidak boleh dihapus.");
        }

        if ((int) $actor->id === (int) $target->id) {
            abort(422, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (!$this->canManageUser($actor, $target)) {
            abort(403, 'Akses ditolak.');
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
        if (!$this->canManageUser($actor, $target)) {
            abort(403, 'Akses ditolak.');
        }

        if ($target->isProtectedAccount()) {
            abort(422, "Akun {$target->username} adalah akun terlindung penguji dan password-nya tidak boleh di-reset.");
        }

        $target->update([
            'password'             => Hash::make($target->username),
            'must_change_password' => true,
        ]);

        $target->tokens()->delete();

        return $target;
    }

    /**
     * Alias for resetPasswordWithTempCredential.
     */
    public function resetTemporaryPassword(User $actor, User $target): array
    {
        return $this->resetPasswordWithTempCredential($actor, $target);
    }

    /**
     * Reset a user's password to a random temporary password, revoke old tokens,
     * flag must_change_password = true, and export credentials to storage/app/private.
     */
    public function resetPasswordWithTempCredential(User $actor, User $target): array
    {
        if (!$this->canManageUser($actor, $target)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk me-reset kata sandi pengguna ini.');
        }

        if ($target->isProtectedAccount()) {
            abort(422, "Akun {$target->username} adalah akun terlindung penguji dan password-nya tidak boleh di-reset.");
        }

        $tempPassword = Str::random(12);

        $target->update([
            'password'             => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        $target->tokens()->delete();

        $credentialRow = [
            'id'                 => $target->id,
            'username'           => $target->username,
            'fullname'           => $target->fullname,
            'nip'                => $target->nip ?? '',
            'role_id'            => $target->role_id,
            'temporary_password' => $tempPassword,
            'reset_at'           => now('Asia/Jakarta')->toIso8601String(),
        ];

        $csvPath = $this->writeCredentialsCsv([$credentialRow], "reset_user_{$target->id}");

        return [
            'user'               => $target,
            'temporary_password' => $tempPassword,
            'credentials'        => [$credentialRow],
            'csv_path'           => $csvPath,
        ];
    }

    /**
     * Bulk reset teacher passwords to random temporary passwords (Waka only),
     * revoke old tokens, flag must_change_password = true, and export CSV to storage/app/private.
     * Protected accounts are skipped and reported.
     */
    public function bulkResetTeacherPasswords(User $actor, ?array $userIds = null): array
    {
        if ((int) $actor->role_id !== 1) {
            abort(403, 'Akses ditolak. Hanya Waka Kurikulum yang dapat melakukan reset password massal guru.');
        }

        $query = User::where('role_id', 2)->orderBy('fullname');
        if (!empty($userIds)) {
            $query->whereIn('id', array_map('intval', $userIds));
        }

        $teachers = $query->get();
        $skippedProtected = [];
        $credentials = [];
        $nowIso = now('Asia/Jakarta')->toIso8601String();

        DB::beginTransaction();
        try {
            foreach ($teachers as $teacher) {
                if ($teacher->isProtectedAccount()) {
                    $skippedProtected[] = $teacher->username;
                    continue;
                }

                $tempPassword = Str::random(12);
                $teacher->update([
                    'password'             => Hash::make($tempPassword, ['rounds' => 4]),
                    'must_change_password' => true,
                ]);
                $teacher->tokens()->delete();

                $credentials[] = [
                    'id'                 => $teacher->id,
                    'username'           => $teacher->username,
                    'fullname'           => $teacher->fullname,
                    'nip'                => $teacher->nip ?? '',
                    'role_id'            => $teacher->role_id,
                    'temporary_password' => $tempPassword,
                    'reset_at'           => $nowIso,
                ];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $csvPath = $this->writeCredentialsCsv($credentials, 'bulk_reset_teachers');

        return [
            'count'             => count($credentials),
            'skipped_protected' => $skippedProtected,
            'credentials'       => $credentials,
            'csv_path'          => $csvPath,
        ];
    }

    /**
     * Write credentials array to storage/app/private/credentials/*.csv and return relative path.
     */
    public function writeCredentialsCsv(array $rows, string $prefix = 'credentials'): string
    {
        $timestamp = now('Asia/Jakarta')->format('Ymd_His') . '_' . Str::lower(Str::random(4));
        $relativePath = "credentials/{$prefix}_{$timestamp}.csv";

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['id', 'username', 'fullname', 'nip', 'role_id', 'temporary_password', 'reset_at']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['id'] ?? '',
                $row['username'] ?? '',
                $row['fullname'] ?? '',
                $row['nip'] ?? '',
                $row['role_id'] ?? '',
                $row['temporary_password'] ?? '',
                $row['reset_at'] ?? '',
            ]);
        }
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($relativePath, $csvContent);

        return $relativePath;
    }

    /**
     * Check whether an actor is authorized to manage (view, update, delete, reset-password) a target user.
     */
    public function canManageUser(User $actor, User $target): bool
    {
        if ((int) $actor->role_id === 1) {
            return true;
        }

        if ($actor->hasKesiswaanAccess()) {
            if ((int) $target->role_id !== 4) {
                return false;
            }

            $allowedClassIds = $actor->supervisedClassIds();
            if ($allowedClassIds !== null) {
                if ($target->class_id === null || !in_array((int) $target->class_id, $allowedClassIds, true)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
