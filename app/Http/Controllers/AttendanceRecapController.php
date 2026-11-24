<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceRecapController extends Controller
{
    /**
     * Display the dedicated monthly attendance recap and class-by-class breakdown.
     */
    public function index(Request $request, DashboardService $dashboards): View
    {
        $config = SystemConfiguration::first();
        $selectedMonth = $request->query('month', Carbon::now('Asia/Jakarta')->format('Y-m'));
        $attendanceOverview = $dashboards->wakaAttendanceOverview($selectedMonth);

        return view('attendance.recap', compact(
            'config',
            'selectedMonth',
            'attendanceOverview'
        ));
    }
}
