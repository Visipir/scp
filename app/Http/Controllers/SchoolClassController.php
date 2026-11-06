<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Services\SchoolClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolClassController extends Controller
{
    public function __construct(
        private readonly SchoolClassService $classService
    ) {}

    public function index(Request $request): View
    {
        $academicYears = SchoolClass::getDistinctAcademicYears();
        $selectedYear = $request->query('academic_year');
        $selectedGrade = $request->query('grade_level');
        $selectedMajor = $request->query('major');

        $classes = $this->classService->listClasses(
            $request->user(),
            [
                'academic_year' => $selectedYear,
                'grade_level'   => $selectedGrade,
                'major'         => $selectedMajor,
            ],
            15
        );

        $gradeLevels = SchoolClass::whereNotNull('grade_level')
            ->select('grade_level')->distinct()->pluck('grade_level')->sort()->values();
        $majors = SchoolClass::whereNotNull('major')
            ->select('major')->distinct()->pluck('major')->sort()->values();

        return view('classes.index', compact(
            'classes', 'academicYears', 'selectedYear',
            'gradeLevels', 'selectedGrade', 'majors', 'selectedMajor'
        ));
    }

    public function show(Request $request, SchoolClass $class): View
    {
        $data = $this->classService->getClassDetail(
            $request->user(),
            $class,
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('search')
        );

        return view('classes.show', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name'    => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
        ], [
            'class_name.required'    => 'Nama kelas wajib diisi.',
            'academic_year.required' => 'Tahun ajaran wajib diisi (contoh: 2026/2027).',
        ]);

        $created = $this->classService->createClass($request->user(), $validated);

        return redirect()->route('classes.index', ['academic_year' => $created->academic_year])
            ->with('success', "Kelas {$created->class_name} ({$created->academic_year}) berhasil ditambahkan.");
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'class_name'    => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
        ], [
            'class_name.required'    => 'Nama kelas wajib diisi.',
            'academic_year.required' => 'Tahun ajaran wajib diisi.',
        ]);

        $updated = $this->classService->updateClass($request->user(), $class, $validated);

        return redirect()->route('classes.index', ['academic_year' => $updated->academic_year])
            ->with('success', "Data kelas {$updated->class_name} berhasil diperbarui.");
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        $scopedClassId = $request->user()?->scopedClassId();
        abort_if($scopedClassId !== null && (int) $class->id !== $scopedClassId, 403, 'Akses ditolak.');

        if ($error = $this->classService->checkDeletable($class)) {
            return back()->with('error', $error);
        }

        $className = $class->class_name;
        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', "Kelas {$className} berhasil dihapus.");
    }

    public function exportAttendance(Request $request, SchoolClass $class): StreamedResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        return $this->classService->exportAttendanceStream(
            $request->user(),
            $class,
            $request->input('start_date'),
            $request->input('end_date')
        );
    }

    public function updateAssignments(Request $request, SchoolClass $class): RedirectResponse
    {
        abort_unless((int) $request->user()->role_id === 1, 403, 'Hanya Waka Kurikulum yang dapat mengubah penugasan.');

        $validated = $request->validate([
            'homeroom_user_id'     => ['nullable', 'exists:users,id'],
            'counselor_user_ids'   => ['nullable', 'array'],
            'counselor_user_ids.*' => ['exists:users,id'],
        ]);

        $academicYear = $class->academic_year ?? '2026/2027';

        // Update Homeroom
        if (!empty($validated['homeroom_user_id'])) {
            \App\Models\HomeroomAssignment::updateOrCreate(
                ['class_id' => $class->id],
                [
                    'user_id'       => $validated['homeroom_user_id'],
                    'academic_year' => $academicYear,
                ]
            );
        } else {
            \App\Models\HomeroomAssignment::where('class_id', $class->id)->delete();
        }

        // Update Counselors: sync for this class
        \App\Models\CounselorAssignment::where('class_id', $class->id)->delete();
        if (!empty($validated['counselor_user_ids'])) {
            foreach (array_unique($validated['counselor_user_ids']) as $counselorId) {
                \App\Models\CounselorAssignment::create([
                    'user_id'       => $counselorId,
                    'class_id'      => $class->id,
                    'academic_year' => $academicYear,
                ]);
            }
        }

        return back()->with('success', "Penugasan Wali Kelas & Guru BK untuk {$class->class_name} berhasil diperbarui.");
    }
}
