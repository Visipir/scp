<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClassResource;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function classes(): JsonResponse
    {
        return ApiResponse::list(
            SchoolClass::orderBy('class_name')->get(),
            ClassResource::class,
        );
    }

    public function rooms(): JsonResponse
    {
        return ApiResponse::list(
            Room::orderBy('room_name')->get(),
            RoomResource::class,
        );
    }

    public function teachers(): JsonResponse
    {
        return ApiResponse::list(
            User::where('role_id', 2)->orderBy('fullname')->get(),
            TeacherResource::class,
        );
    }
}
