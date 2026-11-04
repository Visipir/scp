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
        $request->validate([
            'email'      => ['required_without:identifier', 'nullable', 'string'],
            'identifier' => ['required_without:email', 'nullable', 'string'],
        ], [
            'email.required_without'      => 'Email atau Nama Pengguna / NIS wajib diisi.',
            'identifier.required_without' => 'Email atau Nama Pengguna / NIS wajib diisi.',
        ]);

        $identifier = (string) ($request->input('identifier') ?: $request->input('email'));
        $this->resetService->requestResetCode($identifier);

        return redirect()->route('password.reset.code', ['email' => $identifier, 'identifier' => $identifier])
            ->with('status', 'Jika akun Anda terdaftar dan memiliki email terverifikasi, kode verifikasi 6 digit telah dikirimkan. Silakan periksa kotak masuk atau spam.');
    }

    public function showResetForm(Request $request): View
    {
        $identifier = $request->query('identifier', $request->query('email', old('identifier', old('email', ''))));

        return view('auth.reset-password-code', compact('identifier'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'      => ['required_without:identifier', 'nullable', 'string'],
            'identifier' => ['required_without:email', 'nullable', 'string'],
            'code'       => ['required', 'string', 'size:6'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required_without'      => 'Email atau Nama Pengguna / NIS wajib diisi.',
            'identifier.required_without' => 'Email atau Nama Pengguna / NIS wajib diisi.',
            'code.required'               => 'Kode verifikasi 6 digit wajib diisi.',
            'code.size'                   => 'Kode verifikasi harus 6 digit angka.',
            'password.required'           => 'Kata sandi baru wajib diisi.',
            'password.min'                => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed'          => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $identifier = (string) ($request->input('identifier') ?: $request->input('email'));

        try {
            $this->resetService->verifyAndReset(
                $identifier,
                $validated['code'],
                $validated['password']
            );

            return redirect()->route('login')
                ->with('status', 'Kata sandi Anda berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput($request->only('identifier', 'email', 'code'))
                ->withErrors($e->errors());
        }
    }
}
