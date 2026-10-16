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

        $target = $service->resolveTargetStudent($user, isset($validated['student_id']) ? (int) $validated['student_id'] : null);

        $student = $target['student'];
        $studentId = $target['student_id'];
        $isParent = $target['is_parent'];
        $children = $target['children'];

        ['summary' => $summary, 'records' => $records] = $service->forStudent($studentId, $month);

        return view('student.attendance', compact(
            'student', 'summary', 'records', 'month', 'children', 'isParent'
        ));
    }
}
