<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $guarded = [];

    public static function resolveOrCreate(?string $name): self
    {
        $clean = trim((string)$name);
        if ($clean === '') {
            $clean = 'Ruang 1';
        }

        if (preg_match('/^\d+$/', $clean)) {
            $clean = 'Ruang ' . $clean;
        } elseif (preg_match('/^(?:teori|r\.?\s*teori|r\.?)\s*(\d+)$/i', $clean, $m)) {
            $clean = 'Ruang ' . $m[1];
        } elseif (preg_match('/^(?:lab\.?|l\.?|lab)\s*(\d+)$/i', $clean, $m)) {
            $clean = 'Lab ' . $m[1];
        } elseif (preg_match('/^(?:or|lapangan)\s*(\d+)$/i', $clean, $m)) {
            $clean = 'Lapangan OR ' . $m[1];
        }

        $compact = str_replace(['.', ' '], '', strtolower($clean));
        $all = self::orderBy('id')->get();
        foreach ($all as $r) {
            $rCompact = str_replace(['.', ' '], '', strtolower($r->room_name));
            if ($rCompact === $compact || str_replace('lab', 'l', $rCompact) === str_replace('lab', 'l', $compact)) {
                return $r;
            }
        }

        return self::firstOrCreate(['room_name' => $clean]);
    }
}
