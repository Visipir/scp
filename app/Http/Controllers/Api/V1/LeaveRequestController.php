<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LeaveIndexRequest;
use App\Http\Requests\Api\V1\StoreLeaveRequestRequest;
use App\Http\Requests\Api\V1\UpdateLeaveStatusRequest;
use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(LeaveIndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
            ->visibleTo($request->user());

        if ($request->filled('status_approval')) {
            $query->where('status_approval', $request->input('status_approval'));
        }

        $paginator = $query->latest()->paginate($perPage);

        return ApiResponse::paginated($paginator, LeaveRequestResource::class);
    }

    public function store(StoreLeaveRequestRequest $request, LeaveRequestService $service): JsonResponse
    {
        $leaveRequest = $service->create(
            $request->user(),
            $request->validated(),
            $request->file('document'),
        );

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);

        return ApiResponse::item(new LeaveRequestResource($leaveRequest), 201);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);

        return ApiResponse::item(new LeaveRequestResource($leaveRequest));
    }

    public function document(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');
        abort_if(blank($leaveRequest->document_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($leaveRequest->document_path), 404);

        return $disk->response($leaveRequest->document_path);
    }

    public function updateStatus(UpdateLeaveStatusRequest $request, LeaveRequest $leaveRequest, LeaveRequestService $service): JsonResponse
    {
        abort_unless($leaveRequest->isVisibleTo($request->user()), 403, 'Akses ditolak.');

        $service->updateStatus($leaveRequest, $request->validated()['status_approval'], $request->user());

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);

        return ApiResponse::item(new LeaveRequestResource($leaveRequest));
    }
}
