<?php

namespace App\Services;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassPromotionService
{
    /**
     * Get baseline data needed for promotion and assignment pages.
     */
    public function getPromotionData(): array
    {
        $classes = SchoolClass::with(['students', 'homeroomTeacher', 'counselors'])
            ->orderBy('class_name')
            ->get();

        $teachers = User::whereIn('role_id', [1, 2, 3, 5])
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username', 'nip', 'role_id']);

        $academicYears = SchoolClass::getDistinctAcademicYears();

        return compact('classes', 'teachers', 'academicYears');
    }

    /**
     * Simulate promoting students from source class to target class or graduation.
     */
    public function simulatePromotion(int $sourceClassId, ?int $targetClassId, array $studentIds = [], string $action = 'promote'): array
    {
        $sourceClass = SchoolClass::with('students')->findOrFail($sourceClassId);
        $targetClass = ($action === 'promote' && $targetClassId) ? SchoolClass::find($targetClassId) : null;

        $query = User::where('role_id', 4)->where('class_id', $sourceClassId);
        if (!empty($studentIds)) {
            $query->whereIn('id', $studentIds);
        }
        $students = $query->orderBy('fullname')->get(['id', 'username', 'fullname', 'gender', 'class_id']);

        $simulatedStudents = $students->map(function ($student) use ($sourceClass, $targetClass, $action) {
            $targetName = match ($action) {
                'graduate' => 'Lulus / Alumni (Tamat Belajar)',
                'stay'     => $sourceClass->class_name . ' (Tinggal Kelas)',
                default    => $targetClass?->class_name ?? 'Belum Ditentukan',
            };

            return [
                'id'            => $student->id,
                'nis'           => $student->username ?? '-',
                'name'          => $student->fullname ?? $student->name,
                'gender'        => $student->gender ?? '-',
                'current_class' => $sourceClass->class_name,
                'target_class'  => $targetName,
                'action_label'  => match ($action) {
                    'graduate' => 'Kelulusan Siswa',
                    'stay'     => 'Tinggal di Kelas Asal',
                    default    => 'Kenaikan / Mutasi Kelas',
                },
            ];
        });

        return [
            'source_class'       => $sourceClass,
            'target_class'       => $targetClass,
            'action'             => $action,
            'students'           => $simulatedStudents,
            'total_students'     => $simulatedStudents->count(),
            'source_class_total' => $sourceClass->students->count(),
        ];
    }

    /**
     * Commit student promotion or graduation in database.
     */
    public function executePromotion(int $sourceClassId, ?int $targetClassId, array $studentIds, string $action = 'promote'): int
    {
        $sourceClass = SchoolClass::findOrFail($sourceClassId);
        if (empty($studentIds)) {
            return 0;
        }

        return DB::transaction(function () use ($sourceClass, $targetClassId, $studentIds, $action) {
            $query = User::where('role_id', 4)
                ->where('class_id', $sourceClass->id)
                ->whereIn('id', $studentIds);

            if ($action === 'graduate') {
                return $query->update(['class_id' => null]);
            }

            if ($action === 'promote') {
                if (!$targetClassId) {
                    throw new \InvalidArgumentException('Kelas tujuan wajib dipilih untuk proses kenaikan kelas.');
                }
                $targetClass = SchoolClass::findOrFail($targetClassId);
                return $query->update(['class_id' => $targetClass->id]);
            }

            return 0;
        });
    }

    /**
     * Simulate bulk assignment changes for Homeroom and Counselor across classes.
     */
    public function simulateAssignments(array $assignments): array
    {
        $classes = SchoolClass::with(['homeroomTeacher', 'counselors'])->get()->keyBy('id');
        $teachers = User::whereIn('role_id', [1, 2, 3, 5])->get()->keyBy('id');

        $changes = [];

        foreach ($assignments as $classId => $data) {
            $class = $classes->get($classId);
            if (!$class) {
                continue;
            }

            $currentHomeroomId = $class->homeroomTeacher?->id;
            $newHomeroomId = !empty($data['homeroom_user_id']) ? (int) $data['homeroom_user_id'] : null;

            $currentCounselorIds = $class->counselors->pluck('id')->sort()->values()->toArray();
            $newCounselorIds = !empty($data['counselor_user_ids']) 
                ? collect($data['counselor_user_ids'])->map(fn($id) => (int)$id)->sort()->values()->toArray() 
                : [];

            $isHomeroomChanged = ($currentHomeroomId !== $newHomeroomId);
            $isCounselorChanged = ($currentCounselorIds !== $newCounselorIds);

            if ($isHomeroomChanged || $isCounselorChanged) {
                $changes[] = [
                    'class_id'             => $class->id,
                    'class_name'           => $class->class_name,
                    'current_homeroom'     => $class->homeroomTeacher?->fullname ?? '(Kosong)',
                    'new_homeroom'         => $newHomeroomId ? ($teachers->get($newHomeroomId)?->fullname ?? '-') : '(Kosongkan)',
                    'homeroom_changed'     => $isHomeroomChanged,
                    'current_counselors'   => $class->counselors->pluck('fullname')->implode(', ') ?: '(Kosong)',
                    'new_counselors'       => !empty($newCounselorIds) 
                        ? collect($newCounselorIds)->map(fn($id) => $teachers->get($id)?->fullname ?? '-')->implode(', ') 
                        : '(Kosongkan)',
                    'counselor_changed'    => $isCounselorChanged,
                ];
            }
        }

        return [
            'total_classes' => $classes->count(),
            'changed_count' => count($changes),
            'changes'       => $changes,
        ];
    }

    /**
     * Execute bulk assignment updates for Homeroom and Counselor across classes.
     */
    public function executeAssignments(array $assignments, string $academicYear = '2026/2027'): int
    {
        return DB::transaction(function () use ($assignments, $academicYear) {
            $updatedClasses = 0;

            foreach ($assignments as $classId => $data) {
                $class = SchoolClass::find($classId);
                if (!$class) {
                    continue;
                }

                $year = $class->academic_year ?? $academicYear;

                // 1. Homeroom
                if (!empty($data['homeroom_user_id'])) {
                    HomeroomAssignment::updateOrCreate(
                        ['class_id' => $class->id],
                        [
                            'user_id'       => $data['homeroom_user_id'],
                            'academic_year' => $year,
                        ]
                    );
                } else {
                    HomeroomAssignment::where('class_id', $class->id)->delete();
                }

                // 2. Counselors
                CounselorAssignment::where('class_id', $class->id)->delete();
                if (!empty($data['counselor_user_ids']) && is_array($data['counselor_user_ids'])) {
                    foreach (array_unique($data['counselor_user_ids']) as $counselorId) {
                        if ($counselorId) {
                            CounselorAssignment::create([
                                'class_id'      => $class->id,
                                'user_id'       => $counselorId,
                                'academic_year' => $year,
                            ]);
                        }
                    }
                }

                $updatedClasses++;
            }

            return $updatedClasses;
        });
    }
}
