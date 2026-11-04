<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function __construct(
        private readonly MailConfigService $mailConfig
    ) {}

    /**
     * Request adding or changing email. Sends 6-digit code to new email.
     * Sets user->email with email_verified_at = null until confirmed.
     *
     * @throws ValidationException
     */
    public function requestEmailChange(User $user, string $newEmail): array
    {
        $normalizedEmail = strtolower(trim($newEmail));

        if (! filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) || strlen($normalizedEmail) > 255) {
            throw ValidationException::withMessages([
                'email' => ['Format alamat email tidak valid.'],
            ]);
        }

        // Check uniqueness across other users
        $conflict = User::where('email', $normalizedEmail)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email sudah digunakan oleh pengguna lain.'],
            ]);
        }

        // Throttle check: 1 per minute, 5 per hour
        $lastCode = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($lastCode && $lastCode->created_at->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $lastCode->created_at->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => ["Mohon tunggu {$secondsLeft} detik sebelum meminta kode baru."],
            ]);
        }

        $hourlyCount = EmailVerificationCode::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourlyCount >= 5) {
            throw ValidationException::withMessages([
                'email' => ['Batas permintaan kode tercapai (maksimal 5 kali per jam). Silakan coba lagi nanti.'],
            ]);
        }

        $code = sprintf('%06d', random_int(100000, 999999));

        EmailVerificationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email'      => $normalizedEmail,
                'code_hash'  => Hash::make($code),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(15),
            ]
        );

        // Update user record: changing email resets verification
        $user->update([
            'email'             => $normalizedEmail,
            'email_verified_at' => null,
        ]);

        $this->mailConfig->sendEmailVerificationCode($normalizedEmail, $code);

        return [
            'success' => true,
            'message' => 'Kode verifikasi 6 digit telah dikirimkan ke ' . $normalizedEmail . '. Silakan masukkan kode untuk memverifikasi.',
        ];
    }

    /**
     * Verify the 6-digit code and mark email as verified.
     *
     * @throws ValidationException
     */
    public function verifyEmailCode(User $user, string $code): array
    {
        $record = EmailVerificationCode::where('user_id', $user->id)->first();

        if (! $record || $record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid atau telah kedaluwarsa. Silakan ajukan kode baru.'],
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

        // Confirmed!
        $user->update([
            'email'             => $record->email,
            'email_verified_at' => now(),
        ]);

        $record->delete();

        return [
            'success' => true,
            'message' => 'Alamat email berhasil diverifikasi.',
        ];
    }

    /**
     * Remove email from user profile. Requires current password.
     *
     * @throws ValidationException
     */
    public function removeEmail(User $user, string $currentPassword): array
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak sesuai.'],
            ]);
        }

        $user->update([
            'email'             => null,
            'email_verified_at' => null,
        ]);

        EmailVerificationCode::where('user_id', $user->id)->delete();

        return [
            'success' => true,
            'message' => 'Alamat email berhasil dihapus dari akun Anda.',
        ];
    }
}
