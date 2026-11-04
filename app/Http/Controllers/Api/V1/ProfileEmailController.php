<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileEmailController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verificationService
    ) {}

    public function requestChange(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'user'    => new UserResource($request->user()->fresh(['role', 'schoolClass'])),
        ]);
    }

    public function verify(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'user'    => new UserResource($request->user()->fresh(['role', 'schoolClass'])),
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'password'         => ['required_without:current_password', 'nullable', 'string'],
            'current_password' => ['required_without:password', 'nullable', 'string'],
        ], [
            'password.required_without'         => 'Kata sandi saat ini wajib diisi untuk menghapus email.',
            'current_password.required_without' => 'Kata sandi saat ini wajib diisi untuk menghapus email.',
        ]);

        $password = (string) ($request->input('password') ?: $request->input('current_password'));

        $result = $this->verificationService->removeEmail(
            $request->user(),
            $password
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'user'    => new UserResource($request->user()->fresh(['role', 'schoolClass'])),
        ]);
    }
}
