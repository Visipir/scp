<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\Attendance;
use App\Models\IneffectiveDay;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Journal;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'schedule.teacher', 'attendances']);

        // Guru (role 2) only sees their own journals. Waka (role 1) and BK (role 3) supervise all journals
        if ($user->role_id === 2) {
            $query->whereHas('schedule', fn ($q) => $q->where('teacher_id', $user->id));
        }

        $journals = $query->latest('date')->paginate(15);

        return view('journals.index', compact('journals'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->get();

        $kbmSuspendedEvent = \App\Services\AcademicCalendarService::isKbmSuspendedToday(\Carbon\Carbon::today()->toDateString());

        $now = \Carbon\Carbon::now();
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $todayName = $dayMap[$now->format('l')] ?? 'Senin';
        $currentTime = $now->format('H:i:s');

        // Today's schedules for this teacher
        $todaySchedules = $schedules->where('day_of_week', $todayName);

        // Schedule ongoing right now (start_time <= now <= end_time)
        $currentSchedule = $todaySchedules->first(function ($sched) use ($currentTime) {
            return $sched->start_time <= $currentTime && $sched->end_time >= $currentTime;
        });

        // Default schedule: query parameter -> current ongoing -> next upcoming -> first today -> first overall
        $targetScheduleId = $request->query('schedule_id');
        $defaultSchedule = ($targetScheduleId ? $schedules->firstWhere('id', $targetScheduleId) : null)
            ?? $currentSchedule 
            ?? $todaySchedules->first(fn ($sched) => $sched->start_time >= $currentTime)
            ?? $todaySchedules->first()
            ?? $schedules->first();

        $defaultDate = $request->query('date', $now->format('Y-m-d'));

        $classIds = $schedules->pluck('class_id')->unique()->filter();
        $studentsByClass = User::whereIn('class_id', $classIds)
            ->where('role_id', 4) // Siswa
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username', 'gender', 'class_id'])
            ->groupBy('class_id');

        return view('journals.create', compact('schedules', 'kbmSuspendedEvent', 'defaultSchedule', 'defaultDate', 'studentsByClass'));
    }

    public function store(StoreJournalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Verify teacher owns this schedule
        $schedule = Schedule::findOrFail($validated['schedule_id']);
        if ($schedule->teacher_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }

        DB::transaction(function () use ($validated, $request, $schedule) {
            // Handle photo upload INSIDE transaction to prevent orphaned files (auto compress)
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $this->compressAndStorePhoto($request->file('photo'));
            }

            // 1. Create journal
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
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
                return;
            }

            Log::info('[JournalSync] Memulai sinkronisasi presensi.', [
                'total_siswa' => $students->count(),
            ]);

            // 3. Get student IDs with approved leave covering this date
            // Also check by applicant_id (parent submissions) mapped to children
            $studentsOnLeave = LeaveRequest::where('status_approval', 'Approved')
                ->whereDate('start_date', '<=', $validated['date'])
                ->whereDate('end_date', '>=', $validated['date'])
                ->where(function ($query) use ($students) {
                    $studentIds = $students->pluck('id')->toArray();
                    // Match by student_id directly
                    $query->whereIn('student_id', $studentIds);
                })
                ->pluck('student_id')
                ->toArray();

            Log::info('[JournalSync] Izin yang disetujui ditemukan.', [
                'jumlah_izin'  => count($studentsOnLeave),
                'student_ids'  => $studentsOnLeave,
            ]);

            // Map manual absent input from teacher (student_id => [status, notes])
            $manualAbsences = [];
            if (!empty($validated['absent_students'])) {
                foreach ($validated['absent_students'] as $item) {
                    if (!empty($item['student_id']) && !empty($item['status'])) {
                        $manualAbsences[(int) $item['student_id']] = [
                            'status' => $item['status'],
                            'notes'  => $item['notes'] ?? null,
                        ];
                    }
                }
            }

            // 4. Bulk insert attendance records (Default is always Hadir)
            $attendances = $students->map(function (User $student) use ($journal, $validated, $manualAbsences, $studentsOnLeave) {
                // Priority:
                // 1. Manual absence marked by teacher (Sakit, Izin, Alpa, Terlambat)
                // 2. Approved leave request (Izin)
                // 3. Default: Hadir
                
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
                    'journal_id'  => $journal->id,
                    'student_id'  => $student->id,
                    'status'      => $status,
                    'notes'       => $notes,
                    'date'        => $validated['date'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
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
        });

        return redirect()->route('journals.index')
            ->with('success', 'Jurnal berhasil dibuat dan presensi otomatis telah diisi.');
    }

    public function show(Request $request, Journal $journal): View
    {
        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);
        $user = $request->user();

        // Guru (role 2) only sees their own journals. Waka (role 1) and BK (role 3) can monitor all
        if ($user->role_id === 2 && $journal->schedule->teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke jurnal ini.');
        }

        return view('journals.show', compact('journal'));
    }

    /**
     * Auto compress and store journal documentation photo without requiring external packages.
     * Uses PHP GD (built-in standard) to resize and compress down from up to 5MB to ~200-400KB.
     */
    protected function compressAndStorePhoto(UploadedFile $file): string
    {
        $destinationDir = storage_path('app/public/journals');
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $filename = Str::random(40) . '.jpg';
        $destinationPath = $destinationDir . '/' . $filename;
        $relativePath = 'journals/' . $filename;

        // Use standard PHP GD if available (no external packages/extensions required)
        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            try {
                $sourceContent = file_get_contents($file->getRealPath());
                if ($sourceContent !== false) {
                    $sourceImage = @imagecreatefromstring($sourceContent);

                    if ($sourceImage !== false) {
                        // 1. Fix EXIF Rotation
                        if (function_exists('exif_read_data')) {
                            $exif = @exif_read_data($file->getRealPath());
                            if (!empty($exif['Orientation'])) {
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

                        // Max dimensions for documentation photos (1600px keeps great clarity for school evidence)
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
                            
                            // Fill white background for transparent PNGs
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
                            // Adaptive font size based on image width (approx 2% of width)
                            $fontSize = max(14, (int) ($width * 0.02)); 
                            $margin = max(10, (int) ($width * 0.015));

                            // Calculate text bounding box
                            $bbox = imagettfbbox($fontSize, 0, $fontPath, $timestamp);
                            $textWidth = abs($bbox[4] - $bbox[0]);
                            $textHeight = abs($bbox[5] - $bbox[1]);

                            // Bottom Right position
                            $x = $width - $textWidth - $margin - 10;
                            $y = $height - $margin - 10;

                            // Draw semi-transparent background box
                            $bgBox = imagecolorallocatealpha($sourceImage, 0, 0, 0, 50); // 50/127 opacity (black)
                            imagefilledrectangle($sourceImage, $x - 10, $y - $textHeight - 10, $x + $textWidth + 10, $y + 10, $bgBox);

                            // Draw text in Yellow
                            $textColor = imagecolorallocate($sourceImage, 255, 235, 59); // Yellow
                            imagettftext($sourceImage, $fontSize, 0, $x, $y, $textColor, $fontPath, $timestamp);
                        }

                        // Save as JPEG with 75% quality (dramatically reduces size from ~5MB to ~200-400KB)
                        imagejpeg($sourceImage, $destinationPath, 75);
                        imagedestroy($sourceImage);

                        if (file_exists($destinationPath)) {
                            return $relativePath;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('GD Image auto-compression failed, falling back to standard store: ' . $e->getMessage());
            }
        }

        // Graceful Fallback: standard Laravel file store
        return $file->store('journals', 'public');
    }
}
