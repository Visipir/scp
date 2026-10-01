<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->query('role_id');
        $search = $request->query('search');

        $query = User::with('schoolClass');

        if ($roleFilter) {
            $query->where('role_id', $roleFilter);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('role_id')->orderBy('fullname')->paginate(20)->withQueryString();
        
        $roles = [
            1 => 'Waka Kurikulum',
            2 => 'Guru',
            3 => 'Bimbingan Konseling (BK)',
            4 => 'Siswa'
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
            'role_id' => 'required|integer|in:1,2,3,4',
            'class_id' => 'nullable|required_if:role_id,4|exists:classes,id',
            'password' => 'required|string|min:6',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        if ($validated['role_id'] != 4) {
            $validated['class_id'] = null;
        }

        User::create($validated);

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
            'role_id' => 'required|integer|in:1,2,3,4',
            'class_id' => 'nullable|required_if:role_id,4|exists:classes,id',
        ]);

        if ($validated['role_id'] != 4) {
            $validated['class_id'] = null;
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function resetPassword(User $user)
    {
        // Reset password to username by default
        $user->update([
            'password' => Hash::make($user->username),
            'must_change_password' => true,
        ]);

        return redirect()->back()->with('success', "Password pengguna {$user->fullname} berhasil di-reset menjadi username-nya. Pengguna wajib mengganti password saat login berikutnya.");
    }

    public function destroy(User $user)
    {
        // Prevent deleting oneself
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
