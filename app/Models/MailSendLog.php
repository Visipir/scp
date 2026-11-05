<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSendLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function log(string $type, string $rawEmail, bool $success, ?string $errorMessage = null): self
    {
        return self::create([
            'mail_type'     => $type,
            'recipient'     => self::maskEmail($rawEmail),
            'status'        => $success ? 'success' : 'failed',
            'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            'created_at'    => now(),
        ]);
    }

    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 1) . '***' . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
