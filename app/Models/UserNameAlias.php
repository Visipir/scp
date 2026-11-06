<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNameAlias extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Normalize name for alias comparison.
     */
    public static function normalize(string $name): string
    {
        // Uppercase, strip titles, punctuation, multi spaces
        $clean = strtoupper($name);
        $titles = [
            'S.PD.I', 'S.PD.KOR', 'S.PD.SI', 'S.PD', 'S.KOM', 'S.T', 'M.PD', 'M.SI', 'M.S.I',
            'M.ENG', 'M.ACC', 'M.M', 'S.S', 'S.SOS', 'DRS.', 'DRA.', 'DR.', 'PROF.',
            'SPD', 'SKOM', 'MPD', 'MSI', 'MENG', 'SS', 'SSOS', 'DRS', 'DRA'
        ];
        foreach ($titles as $title) {
            $pattern = '/\b' . preg_quote($title, '/') . '\b/i';
            $clean = preg_replace($pattern, '', $clean);
        }
        $clean = preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }
}
