<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        $roleFilter = $request->query('role_id');
        $classFilter = $request->query('class_id');
        $search = $request->query('search');

        $users = $this->userService->listUsers($request->user(), [
            'role_id'    => $roleFilter,
            'class_id'   => $classFilter,
            'student_id' => $request->query('student_id'),
            'search'     => $search,
        ], 15);

        $roles = [
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => 'Bimbingan Konseling (BK)',
            4 => 'Siswa',
        ];
        $roleLabels = $roles + [5 => 'Superadmin'];
        $classes = SchoolClass::orderBy('class_name')->get(['id', 'class_name']);

        return view('users.index', compact('users', 'roles', 'roleLabels', 'classes', 'roleFilter', 'classFilter', 'search'));
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'nullable|email|max:255|unique:users,email',
            'role_id'  => 'required|integer|in:1,2,3,4',
            'class_id' => 'nullable|required_if:role_id,4|exists:classes,id',
            'password' => 'required|string|min:6',
        ]);

        try {
            $this->userService->createUser($request->user(), $validated);
        } catch (HttpException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user)
    {
        abort_unless($this->userService->canManageUser($request->user(), $user), 403, 'Akses ditolak.');
        return redirect()->route('users.index');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email'    => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id'  => 'required|integer|in:1,2,3,4',
            'class_id' => 'nullable|required_if:role_id,4|exists:classes,id',
        ]);

        try {
            $this->userService->updateUser($request->user(), $user, $validated);
        } catch (HttpException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'reset_mode' => ['nullable', 'in:default,custom'],
            'password'   => ['nullable', 'required_if:reset_mode,custom', 'string', 'min:6'],
        ], [
            'password.required_if' => 'Masukkan password baru jika memilih reset password manual.',
            'password.min'         => 'Password baru minimal 6 karakter.',
        ]);

        $mode = $validated['reset_mode'] ?? 'default';

        try {
            if ($mode === 'custom') {
                $this->userService->resetPassword($request->user(), $user, $validated['password']);

                return redirect()->back()
                    ->with('success', "Password pengguna {$user->fullname} berhasil di-reset ke password yang dimasukkan. Pengguna wajib mengganti password saat login berikutnya.");
            }

            $result = $this->userService->resetPasswordWithTempCredential($request->user(), $user);
        } catch (HttpException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', "Password pengguna {$user->fullname} berhasil di-reset ke password sementara acak. Pengguna wajib mengganti password saat login berikutnya.")
            ->with('reset_credentials', $result['credentials'])
            ->with('reset_csv_path', $result['csv_path']);
    }

    public function bulkResetTeacherPasswords(Request $request)
    {
        $userIds = $request->input('user_ids');
        try {
            $result = $this->userService->bulkResetTeacherPasswords(
                $request->user(),
                is_array($userIds) ? $userIds : null
            );
        } catch (HttpException $e) {
            return redirect()->route('users.index')->with('error', $e->getMessage());
        }

        $skippedMsg = !empty($result['skipped_protected'])
            ? ' Akun terlindung dilewati: ' . implode(', ', $result['skipped_protected']) . '.'
            : '';

        return redirect()->route('users.index')
            ->with('success', "Berhasil me-reset password sementara acak untuk {$result['count']} akun guru.{$skippedMsg} Token lama telah dicabut dan kredensial diekspor ke storage/app/private/{$result['csv_path']}.")
            ->with('reset_credentials', $result['credentials'])
            ->with('reset_csv_path', $result['csv_path']);
    }

    public function destroy(Request $request, User $user)
    {
        try {
            $this->userService->deleteUser($request->user(), $user);
        } catch (HttpException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
