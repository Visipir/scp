<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ClassPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassPromotionController extends Controller
{
    public function __construct(
        protected ClassPromotionService $promotionService
    ) {}

    /**
     * Display the promotion and assignment simulation dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->promotionService->getPromotionData();
        $sourceClassId = $request->query('source_class_id');
        $targetClassId = $request->query('target_class_id');
        $action = $request->query('action', 'promote');

        $sourceClass = null;
        $sourceStudents = collect();
        $simulation = null;

        if ($sourceClassId) {
            $sourceClass = SchoolClass::with('students')->find($sourceClassId);
            if ($sourceClass) {
                $sourceStudents = $sourceClass->students()->orderBy('fullname')->get();
                
                // If user requested simulation directly via GET or after simulate POST redirect
                $selectedIds = $request->query('student_ids');
                if (is_string($selectedIds)) {
                    $selectedIds = explode(',', $selectedIds);
                } elseif (!is_array($selectedIds)) {
                    $selectedIds = $sourceStudents->pluck('id')->toArray();
                }

                if ($targetClassId || $action === 'graduate') {
                    $simulation = $this->promotionService->simulatePromotion(
                        (int) $sourceClassId,
                        $targetClassId ? (int) $targetClassId : null,
                        $selectedIds,
                        $action
                    );
                }
            }
        }

        return view('classes.promotion', array_merge($data, compact(
            'sourceClass',
            'sourceClassId',
            'targetClassId',
            'action',
            'sourceStudents',
            'simulation'
        )));
    }

    /**
     * Run simulation and redirect to index with simulation parameters.
     */
    public function simulate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_class_id' => ['required', 'exists:classes,id'],
            'target_class_id' => ['nullable', 'exists:classes,id'],
            'action'          => ['required', 'in:promote,graduate,stay'],
            'student_ids'     => ['nullable', 'array'],
            'student_ids.*'   => ['exists:users,id'],
        ]);

        if ($validated['action'] === 'promote' && empty($validated['target_class_id'])) {
            return back()->withInput()->with('error', 'Silakan pilih kelas tujuan untuk simulasi kenaikan kelas.');
        }

        $studentIds = !empty($validated['student_ids']) ? implode(',', $validated['student_ids']) : '';

        return redirect()->route('classes.promotion', [
            'source_class_id' => $validated['source_class_id'],
            'target_class_id' => $validated['target_class_id'] ?? null,
            'action'          => $validated['action'],
            'student_ids'     => $studentIds,
        ])->with('success', 'Simulasi berhasil diperbarui. Periksa daftar siswa di bawah sebelum menerapkan.');
    }

    /**
     * Execute student promotion or graduation.
     */
    public function execute(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_class_id' => ['required', 'exists:classes,id'],
            'target_class_id' => ['nullable', 'exists:classes,id'],
            'action'          => ['required', 'in:promote,graduate'],
            'student_ids'     => ['required', 'array', 'min:1'],
            'student_ids.*'   => ['exists:users,id'],
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa untuk diproses.',
            'student_ids.min'      => 'Pilih minimal satu siswa untuk diproses.',
        ]);

        if ($validated['action'] === 'promote' && empty($validated['target_class_id'])) {
            return back()->with('error', 'Kelas tujuan wajib dipilih untuk memproses kenaikan kelas.');
        }

        try {
            $count = $this->promotionService->executePromotion(
                (int) $validated['source_class_id'],
                !empty($validated['target_class_id']) ? (int) $validated['target_class_id'] : null,
                $validated['student_ids'],
                $validated['action']
            );

            $actionText = $validated['action'] === 'graduate' ? 'dinyatakan lulus/alumni' : 'berhasil dipindahkan/naik ke kelas tujuan';
            return redirect()->route('classes.promotion')->with('success', "Sebanyak {$count} siswa {$actionText}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses kenaikan kelas: ' . $e->getMessage());
        }
    }

    /**
     * Update homeroom teacher and counselor assignments in bulk.
     */
    public function updateAssignments(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignments'                        => ['required', 'array'],
            'assignments.*.homeroom_user_id'     => ['nullable', 'exists:users,id'],
            'assignments.*.counselor_user_ids'   => ['nullable', 'array'],
            'assignments.*.counselor_user_ids.*' => ['exists:users,id'],
            'mode'                               => ['nullable', 'in:simulate,commit'],
        ]);

        if (($validated['mode'] ?? 'commit') === 'simulate') {
            $simResult = $this->promotionService->simulateAssignments($validated['assignments']);
            return back()->with('assignment_simulation', $simResult)->with('active_tab', 'assignments');
        }

        try {
            $count = $this->promotionService->executeAssignments($validated['assignments']);
            return back()->with('success', "Penugasan Wali Kelas dan Guru BK berhasil diperbarui untuk {$count} kelas.")
                ->with('active_tab', 'assignments');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui penugasan: ' . $e->getMessage())
                ->with('active_tab', 'assignments');
        }
    }
}
