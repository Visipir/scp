<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteUserRequest;
use App\Http\Requests\Api\V1\ResetUserPasswordRequest;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a paginated listing of users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 20);
        $filters = [
            'role_id'    => $request->query('role_id'),
            'class_id'   => $request->query('class_id'),
            'student_id' => $request->query('student_id'),
            'search'     => $request->query('search'),
        ];

        $users = $this->userService->listUsers($request->user(), $filters, $perPage);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->user(), $request->validated());
        $user->load(['role', 'schoolClass']);

        return (new UserResource($user))
            ->additional(['message' => 'Pengguna berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified user detail.
     */
    public function show(Request $request, User $user): UserResource
    {
        $loaded = $this->userService->getUser($request->user(), $user);

        return new UserResource($loaded);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $updated = $this->userService->updateUser($request->user(), $user, $request->validated());

        return (new UserResource($updated))
            ->additional(['message' => 'Data pengguna berhasil diperbarui.']);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(DeleteUserRequest $request, User $user): JsonResponse
    {
        $this->userService->deleteUser($request->user(), $user);

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * Reset user password to their username.
     */
    public function resetPassword(ResetUserPasswordRequest $request, User $user): UserResource
    {
        $reset = $this->userService->resetPassword($request->user(), $user);

        return (new UserResource($reset))
            ->additional(['message' => "Kata sandi pengguna {$user->fullname} berhasil di-reset menjadi username."]);
    }
}
