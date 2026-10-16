<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
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
            $documentPath = $request->file('document')->store('leave-documents', 'local');
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
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

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
