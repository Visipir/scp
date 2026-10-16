<?php

namespace App\Http\Controllers;

use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    /**
     * Attendance recap for a student (role 4) or one of their children (parent).
     */
    public function index(Request $request, AttendanceSummaryService $service): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'month'      => ['nullable', 'date_format:Y-m'],
            'student_id' => ['nullable', 'integer'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        // A parent is a role 4 account that has children linked via parent_id
        $children = $user->children()->where('role_id', 4)->orderBy('fullname')->get();
        $isParent = $children->isNotEmpty();

        if ($isParent) {
            $studentId = (int) ($validated['student_id'] ?? $children->first()->id);

            // Only the parent's own children are allowed
            abort_unless($children->pluck('id')->contains($studentId), 403, 'Akses ditolak.');

            $student = $children->firstWhere('id', $studentId);
        } else {
            // Plain student: always their own records
            $studentId = $user->id;
            $student = $user;
        }

        ['summary' => $summary, 'records' => $records] = $service->forStudent($studentId, $month);

        return view('student.attendance', compact(
            'student', 'summary', 'records', 'month', 'children', 'isParent'
        ));
    }
}
