<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileEmailController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verificationService
    ) {}

    public function requestChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'email.max'      => 'Alamat email maksimal 255 karakter.',
        ]);

        $result = $this->verificationService->requestEmailChange(
            $request->user(),
            $validated['email']
        );

        return redirect()->route('profile.edit')
            ->with('status', 'email-code-sent')
            ->with('success', $result['message']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Kode verifikasi 6 digit wajib diisi.',
            'code.size'     => 'Kode verifikasi harus berupa 6 digit angka.',
        ]);

        $result = $this->verificationService->verifyEmailCode(
            $request->user(),
            $validated['code']
        );

        return redirect()->route('profile.edit')
            ->with('status', 'email-verified')
            ->with('success', $result['message']);
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi untuk menghapus email.',
        ]);

        $result = $this->verificationService->removeEmail(
            $request->user(),
            $validated['current_password']
        );

        return redirect()->route('profile.edit')
            ->with('status', 'email-removed')
            ->with('success', $result['message']);
    }
}
