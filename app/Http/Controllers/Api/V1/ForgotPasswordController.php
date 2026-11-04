<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $resetService
    ) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'      => ['required_without:identifier', 'nullable', 'string'],
            'identifier' => ['required_without:email', 'nullable', 'string'],
        ], [
            'email.required_without'      => 'Email atau Nama Pengguna / NIS wajib diisi.',
            'identifier.required_without' => 'Email atau Nama Pengguna / NIS wajib diisi.',
        ]);

        $identifier = (string) ($request->input('identifier') ?: $request->input('email'));
        $result = $this->resetService->requestResetCode($identifier);

        return ApiResponse::message($result['message']);
    }

    public function resetPassword(Request $request): JsonResponse
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
            'code.size'                   => 'Kode verifikasi harus berupa 6 digit angka.',
            'password.required'           => 'Kata sandi baru wajib diisi.',
            'password.min'                => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'          => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $identifier = (string) ($request->input('identifier') ?: $request->input('email'));

        $result = $this->resetService->verifyAndReset(
            $identifier,
            $validated['code'],
            $validated['password']
        );

        return ApiResponse::message($result['message']);
    }
}
