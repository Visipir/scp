<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class LeaveRequestService
{
    /**
     * Create a leave request after verifying the student belongs to the user.
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(User $user, array $validated, ?UploadedFile $document): LeaveRequest
    {
        $studentId = (int) $validated['student_id'];
        $isOwnRequest = $studentId === $user->id;
        $isChildRequest = $user->children()->where('id', $studentId)->exists();

        if (! $isOwnRequest && ! $isChildRequest) {
            abort(403, 'Anda tidak berhak mengajukan izin untuk siswa ini.');
        }

        $documentPath = null;
        if ($document !== null) {
            $documentPath = $document->store('leave-documents', 'local');
        }

        return LeaveRequest::create([
            'student_id'      => $studentId,
            'applicant_id'    => $user->id,
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'reason'          => $validated['reason'],
            'document_path'   => $documentPath,
            'status_approval' => 'Pending',
        ]);
    }

    /**
     * Approve or reject a leave request. Does NOT touch attendances.
     */
    public function updateStatus(LeaveRequest $leaveRequest, string $status, User $verifier): LeaveRequest
    {
        $leaveRequest->update([
            'status_approval' => $status,
            'verified_by'     => $verifier->id,
        ]);

        return $leaveRequest;
    }
}
