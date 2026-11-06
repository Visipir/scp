<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';
    protected $guarded = [];

    public static function resolveOrCreate(string $name, ?string $cycle = null): self
    {
        $clean = trim($name);
        if ($clean === '') {
            return self::firstOrCreate(['class_name' => 'Umum'], ['academic_year' => '2026/2027']);
        }

        $normalized = preg_replace('/\s+/', ' ', $clean);
        $compact = preg_replace('/[^a-z0-9]/', '', strtolower($clean));

        // Convert leading arabic grade to roman: 10 -> x, 11 -> xi, 12 -> xii
        $compactWithRoman = $compact;
        if (str_starts_with($compact, '10')) {
            $compactWithRoman = 'x' . substr($compact, 2);
        } elseif (str_starts_with($compact, '11')) {
            $compactWithRoman = 'xi' . substr($compact, 2);
        } elseif (str_starts_with($compact, '12')) {
            $compactWithRoman = 'xii' . substr($compact, 2);
        }

        // Map AI hallucination/misalignment aliases for Grade 11
        if ($compactWithRoman === 'xiak4') {
            $compactWithRoman = 'xilps1';
        } elseif ($compactWithRoman === 'xilps') {
            $compactWithRoman = 'xilps2';
        }

        $all = self::orderBy('id')->get();
        foreach ($all as $c) {
            $cCompact = preg_replace('/[^a-z0-9]/', '', strtolower($c->class_name));
            if ($cCompact === $compact || $cCompact === $compactWithRoman) {
                return $c;
            }
        }

        $grades = ($cycle === 'Putaran 2') ? ['x', 'xi', 'xii'] : ['xi', 'xii', 'x'];
        foreach ($grades as $grade) {
            $target = $grade . $compactWithRoman;
            foreach ($all as $c) {
                $cCompact = preg_replace('/[^a-z0-9]/', '', strtolower($c->class_name));
                if ($cCompact === $target) {
                    return $c;
                }
            }
        }

        $canonicalName = preg_replace('/^10\s+/i', 'X ', $normalized);
        $canonicalName = preg_replace('/^11\s+/i', 'XI ', $canonicalName);
        $canonicalName = preg_replace('/^12\s+/i', 'XII ', $canonicalName);

        return self::firstOrCreate(
            ['class_name' => $canonicalName],
            ['academic_year' => '2026/2027']
        );
    }

    public static function getDistinctAcademicYears() { return self::select('academic_year')->distinct()->pluck('academic_year'); }
    public function users() { return $this->hasMany(User::class, 'class_id'); }
    public function students() { return $this->hasMany(User::class, 'class_id')->where('role_id', 4); }
    public function schedules() { return $this->hasMany(Schedule::class, 'class_id'); }
    public function homeroomAssignment() { return $this->hasOne(HomeroomAssignment::class, 'class_id'); }
    public function homeroomTeacher()
    {
        return $this->hasOneThrough(User::class, HomeroomAssignment::class, 'class_id', 'id', 'id', 'user_id');
    }
    public function counselorAssignments() { return $this->hasMany(CounselorAssignment::class, 'class_id'); }
    public function counselors()
    {
        return $this->belongsToMany(User::class, 'counselor_assignments', 'class_id', 'user_id');
    }
}
