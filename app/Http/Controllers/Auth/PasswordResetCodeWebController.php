<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetCodeWebController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $resetService
    ) {}

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
        ]);

        $this->resetService->requestResetCode($validated['email']);

        return redirect()->route('password.reset.code', ['email' => $validated['email']])
            ->with('status', 'Jika email Anda terdaftar, kode verifikasi 6 digit telah dikirimkan. Silakan periksa kotak masuk atau spam.');
    }

    public function showResetForm(Request $request): View
    {
        $email = $request->query('email', old('email', ''));

        return view('auth.reset-password-code', compact('email'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format alamat email tidak valid.',
            'code.required'      => 'Kode verifikasi 6 digit wajib diisi.',
            'code.size'          => 'Kode verifikasi harus 6 digit angka.',
            'password.required'  => 'Kata sandi baru wajib diisi.',
            'password.min'       => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        try {
            $this->resetService->verifyAndReset(
                $validated['email'],
                $validated['code'],
                $validated['password']
            );

            return redirect()->route('login')
                ->with('status', 'Kata sandi Anda berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput($request->only('email', 'code'))
                ->withErrors($e->errors());
        }
    }
}
