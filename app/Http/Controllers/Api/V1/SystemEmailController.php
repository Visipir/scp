<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MailConfigService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SystemEmailController extends Controller
{
    public function __construct(
        private readonly MailConfigService $mailService
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::data($this->mailService->getSafeSettings());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'between:1,65535'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $this->mailService->updateSettings($validated, $request->user()->id);

        return ApiResponse::data($this->mailService->getSafeSettings());
    }

    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user();

        if (empty($user->email) || empty($user->email_verified_at)) {
            return ApiResponse::message(
                'Akun Anda belum memiliki alamat email yang terverifikasi untuk menerima email tes. Silakan verifikasi email di menu Profil terlebih dahulu.',
                422
            );
        }

        try {
            $this->mailService->sendTestEmail($user->email, $user->fullname);

            return ApiResponse::message('Email tes berhasil dikirimkan ke: ' . $user->email);
        } catch (RuntimeException $e) {
            return ApiResponse::message($e->getMessage(), 500);
        }
    }
}
