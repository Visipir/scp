<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * List leave requests.
     * Role 1 and BK: see all requests.
     * Role 3 (Wali Kelas): only their homeroom class.
     * Role 4: see only own requests (as student or as applicant/parent).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
            ->visibleTo($user);

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status_approval', $status);
        }

        $leaveRequests = $query->latest()->paginate(15);

        return view('leave-requests.index', compact('leaveRequests'));
    }

    /**
     * Show a single leave request.
     * Role 4 can only see their own requests, Wali Kelas only their homeroom class.
     */
    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);

        return view('leave-requests.show', compact('leaveRequest'));
    }

    /**
     * Stream the private leave document to an authorized user (inline).
     */
    public function document(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

        abort_if(blank($leaveRequest->document_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($leaveRequest->document_path), 404);

        return $disk->response($leaveRequest->document_path);
    }

    /**
     * Show the form for creating a new leave request.
     * If the user is a parent, show their linked children for selection.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $children = collect();

        // If parent (has children), load them for selection
        if ($user->children()->exists()) {
            $children = $user->children()->where('role_id', 4)->whereNotNull('class_id')->get(['id', 'fullname', 'class_id']);
            $children->load('schoolClass');
        }

        return view('student.leave-request.create', compact('children'));
    }

    /**
     * Store a new leave request (by student or parent).
     * IDOR Protection: validates that student_id is either self or own child.
     */
    public function store(Request $request, LeaveRequestService $service): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'student_id'  => ['required', 'exists:users,id'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'reason'      => ['required', 'string', 'max:5000'],
            'document'    => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
        ]);

        $service->create($user, $validated, $request->file('document'));

        return redirect()->route('leave-requests.index')
            ->with('success', 'Permohonan izin berhasil diajukan.');
    }

    /**
     * Approve or reject a leave request (Tim Kesiswaan / Waka only).
     */
    public function updateStatus(Request $request, LeaveRequest $leaveRequest, LeaveRequestService $service): RedirectResponse
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

        $validated = $request->validate([
            'status_approval' => ['required', 'in:Pending,Approved,Rejected'],
        ]);

        $service->updateStatus($leaveRequest, $validated['status_approval'], $request->user());

        $statusMap = [
            'Approved' => 'disetujui',
            'Rejected' => 'ditolak',
            'Pending'  => 'diubah menjadi status menunggu persetujuan',
        ];
        $statusText = $statusMap[$validated['status_approval']] ?? 'diperbarui';

        return redirect()->route('leave-requests.show', $leaveRequest)
            ->with('success', "Permohonan izin telah {$statusText}.");
    }
}
