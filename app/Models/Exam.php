<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject',
        'category',
        'title',
        'exam_date',
        'start_time',
        'end_time',
        'status',
        'description',
        'material_file_path',
        'file_name',
        'file_size',
        'external_link',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'file_size' => 'integer',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get human-friendly category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'bank_soal' => 'Bank Soal & Tugas',
            'ujian' => 'Ujian & Asesmen',
            default => 'Materi Pembelajaran',
        };
    }

    /**
     * Get badge configuration for category.
     */
    public function getCategoryBadgeAttribute(): array
    {
        return match ($this->category) {
            'bank_soal' => [
                'bg' => 'bg-sky-50 text-sky-800 border-sky-200',
                'dot' => 'bg-sky-500',
                'label' => 'Bank Soal & Tugas',
                'icon' => 'document-text',
            ],
            'ujian' => [
                'bg' => 'bg-rose-50 text-rose-800 border-rose-200',
                'dot' => 'bg-rose-500',
                'label' => 'Ujian & Asesmen',
                'icon' => 'academic-cap',
            ],
            default => [
                'bg' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'dot' => 'bg-emerald-500',
                'label' => 'Materi Pembelajaran',
                'icon' => 'book-open',
            ],
        };
    }

    /**
     * Format file size to human readable (KB, MB).
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Extract file extension.
     */
    public function getFileExtensionAttribute(): ?string
    {
        if ($this->file_name) {
            return strtoupper(pathinfo($this->file_name, PATHINFO_EXTENSION));
        }
        if ($this->material_file_path) {
            return strtoupper(pathinfo($this->material_file_path, PATHINFO_EXTENSION));
        }
        return null;
    }

    /**
     * Time status calculation (Hari ini, Mendatang, Selesai).
     */
    public function getTimeStatusAttribute(): array
    {
        $today = Carbon::today();
        $date = Carbon::parse($this->exam_date)->startOfDay();

        if ($date->equalTo($today)) {
            return [
                'text' => 'Hari Ini',
                'badge' => 'bg-amber-100 text-amber-900 border-amber-300 animate-pulse',
                'is_today' => true,
            ];
        }

        if ($date->greaterThan($today)) {
            $diffDays = $today->diffInDays($date);
            return [
                'text' => $diffDays <= 7 ? "{$diffDays} Hari Lagi" : 'Mendatang',
                'badge' => 'bg-zinc-100 text-zinc-800 border-zinc-200',
                'is_today' => false,
            ];
        }

        return [
            'text' => 'Selesai',
            'badge' => 'bg-zinc-100 text-zinc-500 border-zinc-200',
            'is_today' => false,
        ];
    }

    /**
     * Format time window string (e.g. 07:30 - 09:00 WIB).
     */
    public function getTimeWindowAttribute(): ?string
    {
        if (!$this->start_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time)->format('H:i');
        if ($this->end_time) {
            $end = Carbon::parse($this->end_time)->format('H:i');
            return "{$start} - {$end} WIB";
        }

        return "Mulai {$start} WIB";
    }
}
