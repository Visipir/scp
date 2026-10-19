<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $examService
    ) {}

    /**
     * Display a listing of exams, question banks, and learning materials.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $exams = $this->examService->listExams($user, [
            'view_scope' => $request->query('view_scope'),
            'category'   => $request->query('category'),
            'class_id'   => $request->query('class_id'),
            'subject'    => $request->query('subject'),
            'search'     => $request->query('search'),
            'sort'       => $request->query('sort', 'date_desc'),
        ], 12);

        $stats = $this->examService->getStats($user, $request->query('view_scope'));

        // Available Filter Options
        if ($user->role_id == 4) {
            $availableClasses = SchoolClass::where('id', $user->class_id)->get();
            $availableSubjects = Exam::where('class_id', $user->class_id)->distinct()->pluck('subject')->filter();
        } elseif ($user->role_id == 2) {
            $teachingClassIds = Schedule::where('teacher_id', $user->id)->distinct()->pluck('class_id');
            $availableClasses = SchoolClass::whereIn('id', $teachingClassIds)->orderBy('class_name')->get();
            if ($availableClasses->isEmpty()) {
                $availableClasses = SchoolClass::orderBy('class_name')->get();
            }
            $availableSubjects = Schedule::where('teacher_id', $user->id)->distinct()->pluck('subject')->filter();
            if ($availableSubjects->isEmpty()) {
                $availableSubjects = Exam::distinct()->pluck('subject')->filter();
            }
        } else {
            $availableClasses = SchoolClass::orderBy('class_name')->get();
            $availableSubjects = Exam::distinct()->pluck('subject')->filter();
        }

        return view('exams.index', compact('exams', 'stats', 'availableClasses', 'availableSubjects'));
    }

    /**
     * Show the form for creating a new resource (Materi, Bank Soal, or Ujian).
     */
    public function create()
    {
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            abort(403, 'Akses terbatas untuk Pengajar dan Waka Kurikulum.');
        }

        $allClasses = SchoolClass::orderBy('class_name')->get();

        if ($user->role_id == 2) {
            $schedules = Schedule::with('schoolClass')
                ->where('teacher_id', $user->id)
                ->get();

            $teachingPairs = $schedules->unique(function ($s) {
                return $s->class_id . '-' . $s->subject;
            });

            $teacherSubjects = $schedules->pluck('subject')->unique()->values();
        } else {
            $teachingPairs = collect();
            $teacherSubjects = Schedule::distinct()->pluck('subject')->filter()->values();
        }

        return view('exams.create', compact('allClasses', 'teachingPairs', 'teacherSubjects'));
    }

    /**
     * Store newly created resources in storage.
     * Supports broadcasting to multiple classes simultaneously.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            abort(403);
        }

        $validated = $request->validate([
            'category'      => 'required|in:materi,bank_soal,ujian',
            'title'         => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'class_ids'     => 'required|array|min:1',
            'class_ids.*'   => 'exists:classes,id',
            'exam_date'     => 'required|date',
            'start_time'    => 'nullable|date_format:H:i',
            'end_time'      => 'nullable|date_format:H:i',
            'description'   => 'nullable|string',
            'external_link' => 'nullable|url|max:500',
            'material_file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip,xls,xlsx,jpg,jpeg,png,rar|max:51200',
        ], [
            'class_ids.required'  => 'Pilih minimal satu kelas tujuan materi/ujian.',
            'category.required'   => 'Pilih jenis konten (Materi, Bank Soal, atau Ujian).',
            'material_file.max'   => 'Ukuran berkas maksimal adalah 50MB.',
            'material_file.mimes' => 'Format berkas harus berupa dokumen, presentasi, arsip ZIP/RAR, atau gambar.',
            'external_link.url'   => 'Format tautan eksternal tidak valid (harus diawali http:// atau https://).',
        ]);

        $created = $this->examService->createExams($user, $validated, $request->file('material_file'));

        $categoryLabel = match ($validated['category']) {
            'bank_soal' => 'Bank Soal & Tugas',
            'ujian'     => 'Jadwal Ujian & Asesmen',
            default     => 'Materi Pembelajaran',
        };

        return redirect()->route('exams.index')
            ->with('success', "Berhasil mempublikasikan {$categoryLabel} ke " . $created->count() . " kelas.");
    }

    /**
     * Display the specified resource details.
     */
    public function show(Exam $exam)
    {
        $user = auth()->user();
        $exam = $this->examService->getExamDetail($user, $exam);

        return view('exams.show', compact('exam'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam)
    {
        $user = auth()->user();

        if ($user->role_id != 1 && ($user->role_id != 2 || $exam->teacher_id != $user->id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah konten ini.');
        }

        $allClasses = SchoolClass::orderBy('class_name')->get();
        $exam->load('schoolClass', 'teacher');

        return view('exams.edit', compact('exam', 'allClasses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        $user = auth()->user();

        if ($user->role_id != 1 && ($user->role_id != 2 || $exam->teacher_id != $user->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'category'      => 'required|in:materi,bank_soal,ujian',
            'title'         => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'class_id'      => 'required|exists:classes,id',
            'exam_date'     => 'required|date',
            'start_time'    => 'nullable|date_format:H:i',
            'end_time'      => 'nullable|date_format:H:i',
            'description'   => 'nullable|string',
            'external_link' => 'nullable|url|max:500',
            'material_file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip,xls,xlsx,jpg,jpeg,png,rar|max:51200',
            'remove_file'   => 'nullable|boolean',
        ]);

        $this->examService->updateExam(
            $user,
            $exam,
            $validated,
            $request->file('material_file'),
            $request->boolean('remove_file')
        );

        return redirect()->route('exams.index')->with('success', 'Perubahan materi/ujian berhasil disimpan.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam)
    {
        $user = auth()->user();
        $this->examService->deleteExam($user, $exam);

        return redirect()->route('exams.index')->with('success', 'Materi/ujian berhasil dihapus.');
    }

    /**
     * Securely download the attached file with original file name.
     */
    public function download(Exam $exam): StreamedResponse
    {
        $user = auth()->user();

        if (!$exam->material_file_path || !Storage::disk('public')->exists($exam->material_file_path)) {
            return back()->with('error', 'Berkas lampiran tidak ditemukan di server.');
        }

        return $this->examService->downloadExam($user, $exam);
    }
}

