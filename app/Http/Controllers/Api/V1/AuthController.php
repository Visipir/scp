<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->input('username'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return ApiResponse::message('Username atau password salah.', 401);
        }

        $deviceName = $request->filled('device_name')
            ? (string) $request->input('device_name')
            : 'ios';

        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiResponse::data([
            'token'                => $token,
            'user'                 => (new UserResource($user->load(['role', 'schoolClass'])))->resolve(),
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::message('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'schoolClass', 'children.schoolClass']);

        return ApiResponse::item(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request, AuthService $service): JsonResponse
    {
        $user = $service->updateProfile($request->user(), $request->validated());

        return ApiResponse::item(new UserResource($user->load(['role', 'schoolClass'])));
    }

    public function changePassword(ChangePasswordRequest $request, AuthService $service): JsonResponse
    {
        $service->changePassword($request->user(), (string) $request->validated()['password']);

        return ApiResponse::message('Kata sandi berhasil diperbarui.');
    }
}
