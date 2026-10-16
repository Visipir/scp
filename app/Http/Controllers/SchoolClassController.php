<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = SchoolClass::getDistinctAcademicYears();
        $selectedYear = $request->query('academic_year');
        $selectedGrade = $request->query('grade_level');
        $selectedMajor = $request->query('major');

        $query = SchoolClass::withCount('users as students_count');

        // Wali Kelas (role 3 with class_id) only sees their homeroom class
        $scopedClassId = $request->user()?->scopedClassId();
        if ($scopedClassId !== null) {
            $query->where('id', $scopedClassId);
        }

        if ($selectedYear && $selectedYear !== 'all') {
            $query->where('academic_year', $selectedYear);
        }

        if ($selectedGrade && $selectedGrade !== 'all') {
            $query->where('grade_level', $selectedGrade);
        }

        if ($selectedMajor && $selectedMajor !== 'all') {
            $query->where('major', $selectedMajor);
        }

        $classes = $query->orderBy('class_name', 'asc')->paginate(15);

        // Distinct grade levels and majors for filter dropdowns
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
        // Wali Kelas (role 3 with class_id) may only open their homeroom class
        $scopedClassId = $request->user()?->scopedClassId();
        abort_if($scopedClassId !== null && (int) $class->id !== $scopedClassId, 403, 'Akses ditolak.');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = $request->query('search');

        // 1. Get all students in this class
        $studentsQuery = $class->students()
            ->orderByRaw('attendance_number IS NULL, attendance_number ASC')
            ->orderBy('fullname', 'asc');

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id');

        // 2. Query attendances for these students
        $attendanceQuery = Attendance::whereIn('student_id', $studentIds);

        if ($startDate) {
            $attendanceQuery->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $attendanceQuery->whereDate('date', '<=', $endDate);
        }

        $attendances = $attendanceQuery->with(['student', 'journal.schedule'])->get();

        // Group attendances by student_id
        $groupedAttendances = $attendances->groupBy('student_id');

        // 3. Class-wide stats summary
        $totalHadir = $attendances->where('status', 'Hadir')->count();
        $totalSakit = $attendances->where('status', 'Sakit')->count();
        $totalIzin = $attendances->where('status', 'Izin')->count();
        $totalAlpa = $attendances->where('status', 'Alpa')->count();
        $totalTerlambat = $attendances->where('status', 'Terlambat')->count();
        $totalRecords = $attendances->count();

        // Attendance rate
        $attendanceRate = $totalRecords > 0 
            ? round(($totalHadir / $totalRecords) * 100, 1) 
            : 0;

        // 4. Recent absent/issues logs (Sakit, Izin, Alpa, Terlambat)
        $recentAbsences = $attendances->whereIn('status', ['Sakit', 'Izin', 'Alpa', 'Terlambat'])
            ->sortByDesc('date')
            ->take(25);

        return view('classes.show', compact(
            'class',
            'students',
            'groupedAttendances',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpa',
            'totalTerlambat',
            'totalRecords',
            'attendanceRate',
            'recentAbsences',
            'startDate',
            'endDate',
            'search'
        ));
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

        $validated['academic_year'] = trim($validated['academic_year']);
        $validated['class_name'] = trim($validated['class_name']);

        // Auto-parse grade_level and major from class_name
        $parsed = \App\Services\StudentImportService::parseClassName($validated['class_name']);
        $validated['grade_level'] = $parsed['grade_level'];
        $validated['major'] = $parsed['major'];

        SchoolClass::create($validated);

        return redirect()->route('classes.index', ['academic_year' => $validated['academic_year']])
            ->with('success', "Kelas {$validated['class_name']} ({$validated['academic_year']}) berhasil ditambahkan.");
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

        $validated['academic_year'] = trim($validated['academic_year']);
        $validated['class_name'] = trim($validated['class_name']);

        $class->update($validated);

        return redirect()->route('classes.index', ['academic_year' => $validated['academic_year']])
            ->with('success', "Data kelas {$class->class_name} berhasil diperbarui.");
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        if ($class->users()->exists()) {
            return back()->with('error', "Tidak dapat menghapus kelas {$class->class_name} karena masih memiliki data siswa terdaftar.");
        }

        if ($class->schedules()->exists()) {
            return back()->with('error', "Tidak dapat menghapus kelas {$class->class_name} karena terdapat jadwal mengajar yang tertaut.");
        }

        $className = $class->class_name;
        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', "Kelas {$className} berhasil dihapus.");
    }

    public function exportAttendance(Request $request, SchoolClass $class)
    {
        // Wali Kelas (role 3 with class_id) may only export their homeroom class
        $scopedClassId = $request->user()?->scopedClassId();
        abort_if($scopedClassId !== null && (int) $class->id !== $scopedClassId, 403, 'Akses ditolak.');

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $students = $class->students()->orderBy('fullname', 'asc')->get();
        
        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Rekap_Presensi_{$class->class_name}_{$startDate}_sd_{$endDate}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'Nama Siswa', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Terlambat'];

        $callback = function() use($students, $attendances, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $no = 1;
            foreach ($students as $student) {
                $studentAttendances = $attendances->get($student->id, collect());
                
                $row = [
                    $no++,
                    $student->fullname,
                    $studentAttendances->where('status', 'Hadir')->count(),
                    $studentAttendances->where('status', 'Izin')->count(),
                    $studentAttendances->where('status', 'Sakit')->count(),
                    $studentAttendances->where('status', 'Alpa')->count(),
                    $studentAttendances->where('status', 'Terlambat')->count(),
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
