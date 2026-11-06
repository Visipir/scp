<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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
     * Approve, reject, or set pending for a leave request.
     * Re-syncs attendances that already exist within the leave dates.
     * Does NOT create attendance rows when no journal exists.
     */
    public function updateStatus(LeaveRequest $leaveRequest, string $status, User $verifier): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $status, $verifier) {
            $studentClassId = $leaveRequest->student()->value('class_id');

            if ($status === 'Pending') {
                $leaveRequest->update([
                    'status_approval'    => 'Pending',
                    'verified_by'        => null,
                    'decided_by_user_id' => null,
                    'decided_by_role'    => null,
                    'decided_at'         => null,
                ]);
            } else {
                $roleLabel = $this->resolveDeciderRole($verifier, $studentClassId !== null ? (int) $studentClassId : null);
                $leaveRequest->update([
                    'status_approval'    => $status,
                    'verified_by'        => $verifier->id,
                    'decided_by_user_id' => $verifier->id,
                    'decided_by_role'    => $roleLabel,
                    'decided_at'         => now(),
                ]);
            }

            $startDate = Carbon::parse($leaveRequest->start_date)->toDateString();
            $endDate = Carbon::parse($leaveRequest->end_date)->toDateString();

            $attendances = Attendance::where('student_id', $leaveRequest->student_id)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->get();

            if ($attendances->isNotEmpty()) {
                $targetStatus = (stripos($leaveRequest->reason, 'sakit') !== false) ? 'Sakit' : 'Izin';

                foreach ($attendances as $attendance) {
                    if ($status === 'Approved') {
                        if (in_array($attendance->status, ['Alpa', 'Hadir'])) {
                            $attendance->update([
                                'status' => $targetStatus,
                                'notes'  => 'Izin resmi (Pengajuan surat izin disetujui)',
                            ]);
                        }
                    } else { // Pending or Rejected
                        if (in_array($attendance->status, ['Izin', 'Sakit'])) {
                            $attendance->update([
                                'status' => 'Alpa',
                                'notes'  => $status === 'Rejected' ? 'Pengajuan izin ditolak' : 'Pengajuan izin menunggu persetujuan',
                            ]);
                        }
                    }
                }
            }

            return $leaveRequest;
        });
    }

    private function resolveDeciderRole(User $verifier, ?int $studentClassId): string
    {
        if ($studentClassId !== null && $verifier->homeroomAssignment()->where('class_id', $studentClassId)->exists()) {
            return 'Wali';
        }

        if ($studentClassId !== null && $verifier->counselorAssignments()->where('class_id', $studentClassId)->exists()) {
            return 'BK';
        }

        if ((int) $verifier->role_id === 3 || $verifier->counselorAssignments()->exists()) {
            return 'BK';
        }

        if ($verifier->isHomeroomTeacher()) {
            return 'Wali';
        }

        if ((int) $verifier->role_id === 1) {
            return 'Waka';
        }

        return 'BK';
    }
}
