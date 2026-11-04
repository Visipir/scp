<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly MailConfigService $mailConfig
    ) {}

    /**
     * Generate 6-digit code and send email if user exists and has a verified email.
     * Accepts either an email or login ID (username/NIS).
     * Always returns generic response to prevent account enumeration.
     */
    public function requestResetCode(string $identifier): array
    {
        $trimmed = trim($identifier);
        $normalized = strtolower($trimmed);

        // Find user by email or username
        $user = User::where('email', $normalized)
            ->orWhere('username', $trimmed)
            ->first();

        if ($user && ! empty($user->email) && $user->email_verified_at !== null) {
            $userEmail = strtolower(trim($user->email));
            $code = sprintf('%06d', random_int(100000, 999999));

            PasswordResetCode::updateOrCreate(
                ['email' => $userEmail],
                [
                    'code_hash'  => Hash::make($code),
                    'attempts'   => 0,
                    'expires_at' => now()->addMinutes(15),
                ]
            );

            $this->mailConfig->sendPasswordResetCode($userEmail, $code);
        }

        return [
            'success' => true,
            'message' => 'Jika akun ditemukan dan memiliki email terverifikasi, kode dikirim.',
        ];
    }

    /**
     * Verify code and update password.
     * Accepts email or login ID (username/NIS) for the account.
     *
     * @throws ValidationException
     */
    public function verifyAndReset(string $identifier, string $code, string $newPassword): array
    {
        $trimmed = trim($identifier);
        $normalized = strtolower($trimmed);

        $user = User::where('email', $normalized)
            ->orWhere('username', $trimmed)
            ->first();

        $targetEmail = $user?->email ? strtolower(trim($user->email)) : $normalized;
        $record = PasswordResetCode::where('email', $targetEmail)->first();

        if (! $record || $record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.'],
            ]);
        }

        if ($record->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Batas maksimal 5 kali percobaan telah tercapai. Silakan ajukan kode baru.'],
            ]);
        }

        $record->increment('attempts');

        if (! Hash::check(trim($code), $record->code_hash)) {
            $remaining = max(0, 5 - $record->attempts);
            throw ValidationException::withMessages([
                'code' => ["Kode verifikasi salah. Sisa percobaan: {$remaining} kali."],
            ]);
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['Pengguna tidak ditemukan.'],
            ]);
        }

        // Update password & clear must_change_password
        $user->update([
            'password'             => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        // Revoke all Sanctum tokens to force re-login on all devices
        $user->tokens()->delete();

        // Invalidate used code
        $record->delete();

        return [
            'success' => true,
            'message' => 'Kata sandi berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru Anda.',
        ];
    }
}
