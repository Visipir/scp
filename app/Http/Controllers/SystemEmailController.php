<?php

namespace App\Http\Controllers;

use App\Services\MailConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SystemEmailController extends Controller
{
    public function __construct(
        private readonly MailConfigService $mailService
    ) {}

    public function edit(): View
    {
        $settings = $this->mailService->getSafeSettings();

        return view('system-configuration.email', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'between:1,65535'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ], [
            'mail_port.between'      => 'Port email harus di antara 1 dan 65535.',
            'mail_encryption.in'     => 'Enkripsi harus salah satu dari: tls, ssl, atau none.',
            'mail_from_address.email'=> 'Format alamat email pengirim tidak valid.',
        ]);

        $this->mailService->updateSettings($validated, $request->user()->id);

        return redirect()->route('system-configuration.email')
            ->with('success', 'Pengaturan email server SMTP berhasil disimpan.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (empty($user->email) || empty($user->email_verified_at)) {
            return redirect()->route('system-configuration.email')
                ->with('error', 'Gagal mengirim email tes: Akun Anda (' . $user->username . ') belum memiliki alamat email yang terverifikasi. Silakan tambahkan dan verifikasi email di menu Profil terlebih dahulu.');
        }

        try {
            $this->mailService->sendTestEmail($user->email, $user->fullname);

            return redirect()->route('system-configuration.email')
                ->with('success', 'Email tes berhasil dikirimkan ke: ' . $user->email);
        } catch (RuntimeException $e) {
            return redirect()->route('system-configuration.email')
                ->with('error', $e->getMessage());
        }
    }
}
