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
        $search = $request->query('search');

        $users = $this->userService->listUsers($request->user(), [
            'role_id' => $roleFilter,
            'search'  => $search,
        ], 20);

        $roles = [
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => 'Bimbingan Konseling (BK)',
            4 => 'Siswa',
        ];

        return view('users.index', compact('users', 'roles', 'roleFilter', 'search'));
    }

    public function create()
    {
        $classes = SchoolClass::orderBy('class_name')->get();
        return view('users.create', compact('classes'));
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

    public function edit(User $user)
    {
        $classes = SchoolClass::orderBy('class_name')->get();
        return view('users.edit', compact('user', 'classes'));
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
        try {
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
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', "Berhasil me-reset password sementara acak untuk {$result['count']} akun guru. Token lama telah dicabut dan kredensial diekspor ke storage/app/private/{$result['csv_path']}.")
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
