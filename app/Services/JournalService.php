<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JournalService
{
    public const STATUSES = ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'];

    /**
     * Create a journal and sync the class attendance in one transaction.
     */
    public function create(Schedule $schedule, array $validated, ?UploadedFile $photo, ?int $effectiveTeacherId = null, ?int $swapId = null): Journal
    {
        return DB::transaction(function () use ($schedule, $validated, $photo, $effectiveTeacherId, $swapId) {
            // Handle photo upload INSIDE transaction to prevent orphaned files (auto compress)
            $photoPath = null;
            if ($photo !== null) {
                $photoPath = $this->compressAndStorePhoto($photo);
            }

            $swapService = app(ScheduleSwapService::class);
            $check = $swapService->getEffectiveTeacherForSchedule($schedule->id, $validated['date']);
            $finalTeacherId = $effectiveTeacherId ?? $check['teacher_id'];
            $finalSwapId = $swapId ?? $check['swap_id'];

            // 1. Create journal
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
                'teacher_id'        => $finalTeacherId,
                'swap_id'           => $finalSwapId,
                'date'              => $validated['date'],
                'topic_description' => $validated['topic_description'],
                'photo_path'        => $photoPath,
            ]);

            Log::info('[JournalSync] Jurnal dibuat.', [
                'journal_id'  => $journal->id,
                'schedule_id' => $schedule->id,
                'class_id'    => $schedule->class_id,
                'date'        => $validated['date'],
            ]);

            // 2. Fetch all students belonging to this schedule's class
            $students = User::where('class_id', $schedule->class_id)
                ->where('role_id', 4) // Siswa
                ->get();

            if ($students->isEmpty()) {
                Log::info('[JournalSync] Tidak ada siswa di kelas ini. Sync dilewati.', [
                    'class_id' => $schedule->class_id,
                ]);

                return $journal;
            }

            Log::info('[JournalSync] Memulai sinkronisasi presensi.', [
                'total_siswa' => $students->count(),
            ]);

            // 3. Get student IDs with approved leave covering this date
            $studentsOnLeave = LeaveRequest::where('status_approval', 'Approved')
                ->whereDate('start_date', '<=', $validated['date'])
                ->whereDate('end_date', '>=', $validated['date'])
                ->where(function ($query) use ($students) {
                    $query->whereIn('student_id', $students->pluck('id')->toArray());
                })
                ->pluck('student_id')
                ->toArray();

            Log::info('[JournalSync] Izin yang disetujui ditemukan.', [
                'jumlah_izin' => count($studentsOnLeave),
                'student_ids' => $studentsOnLeave,
            ]);

            // Map manual absent input from teacher (student_id => [status, notes])
            $manualAbsences = [];
            if (! empty($validated['absent_students'])) {
                foreach ($validated['absent_students'] as $item) {
                    if (! empty($item['student_id']) && ! empty($item['status'])) {
                        $manualAbsences[(int) $item['student_id']] = [
                            'status' => $item['status'],
                            'notes'  => $item['notes'] ?? null,
                        ];
                    }
                }
            }

            // 4. Bulk insert attendance records (Default is always Hadir)
            $attendances = $students->map(function (User $student) use ($journal, $validated, $manualAbsences, $studentsOnLeave) {
                // Priority: manual -> approved leave (Izin) -> Hadir
                $status = 'Hadir';
                $notes = null;

                if (isset($manualAbsences[$student->id])) {
                    $status = $manualAbsences[$student->id]['status'];
                    $notes = $manualAbsences[$student->id]['notes'];
                } elseif (in_array($student->id, $studentsOnLeave)) {
                    $status = 'Izin';
                    $notes = 'Izin resmi (Pengajuan surat izin disetujui)';
                }

                return [
                    'journal_id' => $journal->id,
                    'student_id' => $student->id,
                    'status'     => $status,
                    'notes'      => $notes,
                    'date'       => $validated['date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            Attendance::insert($attendances);

            $absentCount = count($manualAbsences) + count($studentsOnLeave);
            Log::info('[JournalSync] Presensi berhasil diinsert.', [
                'journal_id'        => $journal->id,
                'total_records'     => count($attendances),
                'total_hadir'       => count($attendances) - $absentCount,
                'total_tidak_hadir' => $absentCount,
            ]);

            return $journal;
        });
    }

    /**
     * Scope a journal query to what the user may list.
     */
    public function scopeForUser(User $user, $query)
    {
        if ((int) $user->role_id === 2) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('schedule', fn ($sq) => $sq->where('teacher_id', $user->id))
                  ->orWhere('teacher_id', $user->id);
            });
        } elseif ((int) $user->role_id === 3) {
            $classId = $user->scopedClassId();
            if ($classId !== null) {
                $query->whereHas('schedule', fn ($q) => $q->where('class_id', $classId));
            }
        }

        return $query;
    }

    /**
     * Whether the user may read this journal's detail.
     */
    public function userCanView(User $user, Journal $journal): bool
    {
        if ((int) $user->role_id === 1) {
            return true;
        }

        if ((int) $user->role_id === 2) {
            return (int) $journal->schedule?->teacher_id === (int) $user->id
                || (int) $journal->teacher_id === (int) $user->id;
        }

        if ((int) $user->role_id === 3) {
            $classId = $user->scopedClassId();

            return $classId === null || (int) $journal->schedule?->class_id === $classId;
        }

        return false;
    }

    /**
     * Whether the user may stream the photo (same rule as the web route).
     */
    public function canViewPhoto(User $user, Journal $journal): bool
    {
        return (int) $user->role_id === 1
            || (int) $user->role_id === 3
            || ((int) $user->role_id === 2 && (int) $journal->schedule?->teacher_id === (int) $user->id);
    }

    /**
     * Grouped attendance status counts for a journal.
     *
     * @return array<string, int>
     */
    public function attendanceSummary(Journal $journal): array
    {
        $counts = $journal->attendances()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [];
        foreach (self::STATUSES as $status) {
            $summary[$status] = (int) ($counts[$status] ?? 0);
        }
        $summary['Total'] = array_sum($summary);

        return $summary;
    }

    /**
     * Roster for a schedule on a date, flagging students on approved leave.
     *
     * @return array<int, array<string, mixed>>
     */
    public function preparation(Schedule $schedule, string $date): array
    {
        $students = User::where('class_id', $schedule->class_id)
            ->where('role_id', 4)
            ->orderBy('fullname')
            ->get();

        $leaves = LeaveRequest::where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return $students->map(function (User $student) use ($leaves) {
            $leave = $leaves->get($student->id);

            return [
                'student_id'        => (int) $student->id,
                'fullname'          => $student->fullname,
                'username'          => $student->username,
                'on_approved_leave' => $leave !== null,
                'leave'             => $leave ? [
                    'id'         => (int) $leave->id,
                    'start_date' => $leave->start_date?->toDateString(),
                    'end_date'   => $leave->end_date?->toDateString(),
                    'reason'     => $leave->reason,
                ] : null,
            ];
        })->values()->all();
    }

    /**
     * Auto compress and store journal documentation photo without external packages.
     */
    public function compressAndStorePhoto(UploadedFile $file): string
    {
        $destinationDir = Storage::disk('local')->path('journals');
        if (! file_exists($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $filename = Str::random(40).'.jpg';
        $destinationPath = $destinationDir.'/'.$filename;
        $relativePath = 'journals/'.$filename;

        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            try {
                $sourceContent = file_get_contents($file->getRealPath());
                if ($sourceContent !== false) {
                    $sourceImage = @imagecreatefromstring($sourceContent);

                    if ($sourceImage !== false) {
                        // 1. Fix EXIF Rotation
                        if (function_exists('exif_read_data')) {
                            $exif = @exif_read_data($file->getRealPath());
                            if (! empty($exif['Orientation'])) {
                                switch ($exif['Orientation']) {
                                    case 3:
                                        $sourceImage = imagerotate($sourceImage, 180, 0);
                                        break;
                                    case 6:
                                        $sourceImage = imagerotate($sourceImage, -90, 0);
                                        break;
                                    case 8:
                                        $sourceImage = imagerotate($sourceImage, 90, 0);
                                        break;
                                }
                            }
                        }

                        $width = imagesx($sourceImage);
                        $height = imagesy($sourceImage);

                        $maxDimension = 1600;
                        if ($width > $maxDimension || $height > $maxDimension) {
                            if ($width > $height) {
                                $newWidth = $maxDimension;
                                $newHeight = (int) round(($height / $width) * $maxDimension);
                            } else {
                                $newHeight = $maxDimension;
                                $newWidth = (int) round(($width / $height) * $maxDimension);
                            }

                            $newImage = imagecreatetruecolor($newWidth, $newHeight);
                            $white = imagecolorallocate($newImage, 255, 255, 255);
                            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
                            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagedestroy($sourceImage);
                            $sourceImage = $newImage;
                            $width = $newWidth;
                            $height = $newHeight;
                        }

                        // 2. Add Timestamp Watermark
                        $timestamp = date('d-m-Y H:i:s');
                        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

                        if (file_exists($fontPath) && function_exists('imagettftext')) {
                            $fontSize = max(14, (int) ($width * 0.02));
                            $margin = max(10, (int) ($width * 0.015));

                            $bbox = imagettfbbox($fontSize, 0, $fontPath, $timestamp);
                            $textWidth = abs($bbox[4] - $bbox[0]);
                            $textHeight = abs($bbox[5] - $bbox[1]);

                            $x = $width - $textWidth - $margin - 10;
                            $y = $height - $margin - 10;

                            $bgBox = imagecolorallocatealpha($sourceImage, 0, 0, 0, 50);
                            imagefilledrectangle($sourceImage, $x - 10, $y - $textHeight - 10, $x + $textWidth + 10, $y + 10, $bgBox);

                            $textColor = imagecolorallocate($sourceImage, 255, 235, 59);
                            imagettftext($sourceImage, $fontSize, 0, $x, $y, $textColor, $fontPath, $timestamp);
                        }

                        imagejpeg($sourceImage, $destinationPath, 75);
                        imagedestroy($sourceImage);

                        if (file_exists($destinationPath)) {
                            return $relativePath;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('GD Image auto-compression failed, falling back to standard store: '.$e->getMessage());
            }
        }

        // Graceful Fallback: standard Laravel file store
        return $file->store('journals', 'local');
    }
}
