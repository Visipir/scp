<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }
    /**
     * Display the first-time password change form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        // If user already changed their password, redirect to dashboard
        if (!$request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.first-change-password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Handle the password update request.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value === $user->username) {
                        $fail('Kata sandi baru tidak boleh sama dengan NIS/Username Anda.');
                    }
                },
            ],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $this->authService->changePassword($user, (string) $request->password);

        return redirect()->route('dashboard')->with('success', 'Kata sandi Anda berhasil diperbarui. Selamat datang di portal siswa!');
    }
}
