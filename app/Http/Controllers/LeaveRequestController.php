<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * List leave requests.
     * Role 1, 3: see all requests.
     * Role 4: see only own requests (as student or as applicant/parent).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier']);

        // Scope: role 4 can only see their own leave requests
        if ($user->role_id === 4) {
            $childrenIds = $user->children()->pluck('id')->toArray();
            $query->where(function ($q) use ($user, $childrenIds) {
                $q->where('student_id', $user->id)
                  ->orWhere('applicant_id', $user->id)
                  ->orWhereIn('student_id', $childrenIds);
            });
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status_approval', $status);
        }

        $leaveRequests = $query->latest()->paginate(15);

        return view('leave-requests.index', compact('leaveRequests'));
    }

    /**
     * Show a single leave request.
     * Role 4 can only see their own requests.
     */
    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        $user = $request->user();

        // Scope check for role 4
        if ($user->role_id === 4) {
            $childrenIds = $user->children()->pluck('id')->toArray();
            $allowed = $leaveRequest->student_id === $user->id
                    || $leaveRequest->applicant_id === $user->id
                    || in_array($leaveRequest->student_id, $childrenIds);

            if (! $allowed) {
                abort(403, 'Akses ditolak.');
            }
        }

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);

        return view('leave-requests.show', compact('leaveRequest'));
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
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'student_id'  => ['required', 'exists:users,id'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'reason'      => ['required', 'string', 'max:5000'],
            'document'    => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
        ]);

        // IDOR Protection: ensure student_id is either self or own child
        $studentId = (int) $validated['student_id'];
        $isOwnRequest = $studentId === $user->id;
        $isChildRequest = $user->children()->where('id', $studentId)->exists();

        if (! $isOwnRequest && ! $isChildRequest) {
            abort(403, 'Anda tidak berhak mengajukan izin untuk siswa ini.');
        }

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leave-documents', 'public');
        }

        LeaveRequest::create([
            'student_id'    => $studentId,
            'applicant_id'  => $user->id,
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'],
            'reason'        => $validated['reason'],
            'document_path' => $documentPath,
        ]);

        return redirect()->route('leave-requests.index')
            ->with('success', 'Permohonan izin berhasil diajukan.');
    }

    /**
     * Approve or reject a leave request (Tim Kesiswaan / Waka only).
     */
    public function updateStatus(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status_approval' => ['required', 'in:Approved,Rejected'],
        ]);

        $leaveRequest->update([
            'status_approval' => $validated['status_approval'],
            'verified_by'     => $request->user()->id,
        ]);

        $statusText = $validated['status_approval'] === 'Approved' ? 'disetujui' : 'ditolak';

        return redirect()->route('leave-requests.show', $leaveRequest)
            ->with('success', "Permohonan izin telah {$statusText}.");
    }
}
