<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamService
{
    /**
     * List exams/materials/question banks with role scoping and filters.
     */
    public function listExams(User $actor, array $filters = [], ?int $perPage = null): EloquentCollection|LengthAwarePaginator
    {
        $query = Exam::with(['schoolClass', 'teacher']);

        // Scope by user role
        if ((int) $actor->role_id === 4) {
            $query->where('class_id', $actor->class_id)
                  ->where('status', 'published');
        } elseif ((int) $actor->role_id === 2) {
            if (($filters['view_scope'] ?? null) !== 'all') {
                $query->where('teacher_id', $actor->id);
            }
        }

        // Filter: Category
        if (!empty($filters['category']) && in_array($filters['category'], ['materi', 'bank_soal', 'ujian'], true)) {
            $query->where('category', $filters['category']);
        }

        // Filter: Class
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        // Filter: Subject
        if (!empty($filters['subject'])) {
            $query->where('subject', $filters['subject']);
        }

        // Search
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('teacher', fn ($t) => $t->where('fullname', 'LIKE', "%{$search}%"));
            });
        }

        // Sort
        $sort = $filters['sort'] ?? 'date_desc';
        if ($sort === 'date_asc') {
            $query->orderBy('exam_date', 'asc')->orderBy('start_time', 'asc');
        } elseif ($sort === 'title_asc') {
            $query->orderBy('title', 'asc');
        } else {
            $query->orderBy('exam_date', 'desc')->orderBy('created_at', 'desc');
        }

        if ($perPage !== null) {
            $perPage = min(max($perPage, 1), 50);
            return $query->paginate($perPage)->withQueryString();
        }

        return $query->get();
    }

    /**
     * Calculate summary statistics for current scope.
     */
    public function getStats(User $actor, ?string $viewScope = null): array
    {
        $statsQuery = Exam::query();

        if ((int) $actor->role_id === 4) {
            $statsQuery->where('class_id', $actor->class_id)->where('status', 'published');
        } elseif ((int) $actor->role_id === 2 && $viewScope !== 'all') {
            $statsQuery->where('teacher_id', $actor->id);
        }

        return [
            'total'     => (clone $statsQuery)->count(),
            'materi'    => (clone $statsQuery)->where('category', 'materi')->count(),
            'bank_soal' => (clone $statsQuery)->where('category', 'bank_soal')->count(),
            'ujian'     => (clone $statsQuery)->where('category', 'ujian')->count(),
            'files'     => (clone $statsQuery)->whereNotNull('material_file_path')->count(),
        ];
    }

    /**
     * Create exams/materials for one or multiple classes.
     */
    public function createExams(User $actor, array $data, ?UploadedFile $file = null): Collection
    {
        abort_if(!in_array((int) $actor->role_id, [1, 2], true), 403, 'Akses terbatas untuk Pengajar dan Waka Kurikulum.');

        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($file) {
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('exam_materials', 'public');
        }

        $classIds = (array) ($data['class_ids'] ?? [$data['class_id']]);
        $created = collect();

        foreach ($classIds as $classId) {
            $exam = Exam::create([
                'teacher_id'         => $actor->id,
                'class_id'           => $classId,
                'subject'            => $data['subject'],
                'category'           => $data['category'],
                'title'              => $data['title'],
                'exam_date'          => $data['exam_date'],
                'start_time'         => $data['start_time'] ?? null,
                'end_time'           => $data['end_time'] ?? null,
                'status'             => 'published',
                'description'        => $data['description'] ?? null,
                'external_link'      => $data['external_link'] ?? null,
                'material_file_path' => $filePath,
                'file_name'          => $fileName,
                'file_size'          => $fileSize,
            ]);

            $created->push($exam->load(['schoolClass', 'teacher']));
        }

        return $created;
    }

    /**
     * Get single exam with ownership check for students.
     */
    public function getExamDetail(User $actor, Exam $exam): Exam
    {
        if ((int) $actor->role_id === 4 && (int) $exam->class_id !== (int) $actor->class_id) {
            abort(403, 'Anda tidak memiliki akses ke materi/ujian kelas lain.');
        }

        return $exam->load(['schoolClass', 'teacher']);
    }

    /**
     * Update exam with ownership check for teachers/admin.
     */
    public function updateExam(
        User $actor,
        Exam $exam,
        array $data,
        ?UploadedFile $file = null,
        bool $removeFile = false
    ): Exam {
        if ((int) $actor->role_id !== 1 && ((int) $actor->role_id !== 2 || (int) $exam->teacher_id !== (int) $actor->id)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah konten ini.');
        }

        $filePath = $exam->material_file_path;
        $fileName = $exam->file_name;
        $fileSize = $exam->file_size;

        if ($removeFile && $filePath) {
            // Check if shared before deleting file from storage
            $isShared = Exam::where('material_file_path', $filePath)->where('id', '!=', $exam->id)->exists();
            if (!$isShared) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = null;
            $fileName = null;
            $fileSize = null;
        }

        if ($file) {
            if ($filePath) {
                $isShared = Exam::where('material_file_path', $filePath)->where('id', '!=', $exam->id)->exists();
                if (!$isShared) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('exam_materials', 'public');
        }

        $exam->update([
            'category'           => $data['category'],
            'title'              => $data['title'],
            'subject'            => $data['subject'],
            'class_id'           => $data['class_id'],
            'exam_date'          => $data['exam_date'],
            'start_time'         => $data['start_time'] ?? null,
            'end_time'           => $data['end_time'] ?? null,
            'description'        => $data['description'] ?? null,
            'external_link'      => $data['external_link'] ?? null,
            'material_file_path' => $filePath,
            'file_name'          => $fileName,
            'file_size'          => $fileSize,
        ]);

        return $exam->fresh()->load(['schoolClass', 'teacher']);
    }

    /**
     * Delete exam with file cleanup and ownership check.
     */
    public function deleteExam(User $actor, Exam $exam): void
    {
        if ((int) $actor->role_id !== 1 && ((int) $actor->role_id !== 2 || (int) $exam->teacher_id !== (int) $actor->id)) {
            abort(403, 'Anda tidak berwenang menghapus konten ini.');
        }

        if ($exam->material_file_path) {
            $isShared = Exam::where('material_file_path', $exam->material_file_path)
                ->where('id', '!=', $exam->id)
                ->exists();

            if (!$isShared) {
                Storage::disk('public')->delete($exam->material_file_path);
            }
        }

        $exam->delete();
    }

    /**
     * Download attached material file.
     */
    public function downloadExam(User $actor, Exam $exam): StreamedResponse
    {
        if ((int) $actor->role_id === 4 && (int) $exam->class_id !== (int) $actor->class_id) {
            abort(403, 'Akses unduh tidak diizinkan.');
        }

        if (!$exam->material_file_path || !Storage::disk('public')->exists($exam->material_file_path)) {
            abort(404, 'Berkas lampiran tidak ditemukan di server.');
        }

        $downloadName = $exam->file_name ?: basename($exam->material_file_path);

        return Storage::disk('public')->download($exam->material_file_path, $downloadName);
    }
}
