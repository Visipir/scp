<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Schedule;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamController extends Controller
{
    /**
     * Display a listing of exams, question banks, and learning materials.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Exam::with(['schoolClass', 'teacher']);

        // Scope by User Role
        if ($user->role_id == 4) {
            // Student: only see materials/exams for their assigned class and published status
            $query->where('class_id', $user->class_id)
                  ->where('status', 'published');
        } elseif ($user->role_id == 2) {
            // Teacher: default show own materials, or allow viewing all if requested
            if ($request->filled('view_scope') && $request->view_scope === 'all') {
                // View all school materials
            } else {
                $query->where('teacher_id', $user->id);
            }
        }

        // Filter: Category (materi, bank_soal, ujian)
        if ($request->filled('category') && in_array($request->category, ['materi', 'bank_soal', 'ujian'])) {
            $query->where('category', $request->category);
        }

        // Filter: Class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter: Subject
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        // Search: Title, Subject, Description, Teacher Name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('teacher', function ($t) use ($search) {
                      $t->where('fullname', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Ordering
        $sort = $request->get('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $query->orderBy('exam_date', 'asc')->orderBy('start_time', 'asc');
        } elseif ($sort === 'title_asc') {
            $query->orderBy('title', 'asc');
        } else {
            $query->orderBy('exam_date', 'desc')->orderBy('created_at', 'desc');
        }

        $exams = $query->paginate(12)->withQueryString();

        // Calculate statistics based on current role scope
        $statsQuery = Exam::query();
        if ($user->role_id == 4) {
            $statsQuery->where('class_id', $user->class_id)->where('status', 'published');
        } elseif ($user->role_id == 2 && (!$request->filled('view_scope') || $request->view_scope !== 'all')) {
            $statsQuery->where('teacher_id', $user->id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'materi' => (clone $statsQuery)->where('category', 'materi')->count(),
            'bank_soal' => (clone $statsQuery)->where('category', 'bank_soal')->count(),
            'ujian' => (clone $statsQuery)->where('category', 'ujian')->count(),
            'files' => (clone $statsQuery)->whereNotNull('material_file_path')->count(),
        ];

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
            // Teacher: retrieve their teaching schedule combinations
            $schedules = Schedule::with('schoolClass')
                ->where('teacher_id', $user->id)
                ->get();

            $teachingPairs = $schedules->unique(function ($s) {
                return $s->class_id . '-' . $s->subject;
            });

            $teacherSubjects = $schedules->pluck('subject')->unique()->values();
        } else {
            // Waka Kurikulum: can publish for any class or subject
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
            'category' => 'required|in:materi,bank_soal,ujian',
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'exam_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'external_link' => 'nullable|url|max:500',
            'material_file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip,xls,xlsx,jpg,jpeg,png,rar|max:20480',
        ], [
            'class_ids.required' => 'Pilih minimal satu kelas tujuan materi/ujian.',
            'category.required' => 'Pilih jenis konten (Materi, Bank Soal, atau Ujian).',
            'material_file.max' => 'Ukuran berkas maksimal adalah 20MB.',
            'material_file.mimes' => 'Format berkas harus berupa dokumen, presentasi, arsip ZIP/RAR, atau gambar.',
            'external_link.url' => 'Format tautan eksternal tidak valid (harus diawali http:// atau https://).',
        ]);

        // Handle File Upload
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('material_file')) {
            $file = $request->file('material_file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('exam_materials', 'public');
        }

        $createdCount = 0;
        foreach ($validated['class_ids'] as $classId) {
            Exam::create([
                'teacher_id' => $user->id,
                'class_id' => $classId,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'title' => $validated['title'],
                'exam_date' => $validated['exam_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'status' => 'published',
                'description' => $validated['description'] ?? null,
                'external_link' => $validated['external_link'] ?? null,
                'material_file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ]);
            $createdCount++;
        }

        $categoryLabel = match ($validated['category']) {
            'bank_soal' => 'Bank Soal & Tugas',
            'ujian' => 'Jadwal Ujian & Asesmen',
            default => 'Materi Pembelajaran',
        };

        return redirect()->route('exams.index')
            ->with('success', "Berhasil mempublikasikan {$categoryLabel} ke {$createdCount} kelas.");
    }

    /**
     * Display the specified resource details.
     */
    public function show(Exam $exam)
    {
        $user = auth()->user();

        // Student can only view their own class's items
        if ($user->role_id == 4 && $exam->class_id != $user->class_id) {
            abort(403, 'Anda tidak memiliki akses ke materi/ujian kelas lain.');
        }

        $exam->load(['schoolClass', 'teacher']);
        return view('exams.show', compact('exam'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam)
    {
        $user = auth()->user();

        // Only Waka (role 1) or the owner teacher can edit
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
            'category' => 'required|in:materi,bank_soal,ujian',
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'exam_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'external_link' => 'nullable|url|max:500',
            'material_file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip,xls,xlsx,jpg,jpeg,png,rar|max:20480',
            'remove_file' => 'nullable|boolean',
        ]);

        $filePath = $exam->material_file_path;
        $fileName = $exam->file_name;
        $fileSize = $exam->file_size;

        // If user wants to remove current file
        if ($request->boolean('remove_file') && $filePath) {
            Storage::disk('public')->delete($filePath);
            $filePath = null;
            $fileName = null;
            $fileSize = null;
        }

        // If new file uploaded
        if ($request->hasFile('material_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $file = $request->file('material_file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('exam_materials', 'public');
        }

        $exam->update([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'class_id' => $validated['class_id'],
            'exam_date' => $validated['exam_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'description' => $validated['description'] ?? null,
            'external_link' => $validated['external_link'] ?? null,
            'material_file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
        ]);

        return redirect()->route('exams.index')->with('success', 'Perubahan materi/ujian berhasil disimpan.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam)
    {
        $user = auth()->user();

        // Only Waka (role 1) or the owner teacher can delete
        if ($user->role_id != 1 && ($user->role_id != 2 || $exam->teacher_id != $user->id)) {
            abort(403, 'Anda tidak berwenang menghapus konten ini.');
        }

        if ($exam->material_file_path) {
            // Check if file is shared by other exams before unlinking
            $isShared = Exam::where('material_file_path', $exam->material_file_path)
                ->where('id', '!=', $exam->id)
                ->exists();

            if (!$isShared) {
                Storage::disk('public')->delete($exam->material_file_path);
            }
        }

        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Materi/ujian berhasil dihapus.');
    }

    /**
     * Securely download the attached file with original file name.
     */
    public function download(Exam $exam)
    {
        $user = auth()->user();

        if ($user->role_id == 4 && $exam->class_id != $user->class_id) {
            abort(403, 'Akses unduh tidak diizinkan.');
        }

        if (!$exam->material_file_path || !Storage::disk('public')->exists($exam->material_file_path)) {
            return back()->with('error', 'Berkas lampiran tidak ditemukan di server.');
        }

        $downloadName = $exam->file_name ?: basename($exam->material_file_path);

        return Storage::disk('public')->download($exam->material_file_path, $downloadName);
    }
}
