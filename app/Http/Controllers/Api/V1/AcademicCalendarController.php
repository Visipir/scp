<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAcademicCalendarRequest;
use App\Http\Resources\Api\V1\AcademicCalendarResource;
use App\Models\AcademicCalendar;
use App\Services\AcademicCalendarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    public function __construct(
        private readonly AcademicCalendarService $calendarService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->has('month') || $request->has('year')) {
            $overview = $this->calendarService->getCalendarOverview(
                $request->has('year') ? (int) $request->input('year') : null,
                $request->has('month') ? (int) $request->input('month') : null
            );

            return response()->json([
                'data' => [
                    'year'            => $overview['year'],
                    'month'           => $overview['month'],
                    'stats'           => $overview['stats'],
                    'today_suspended' => $overview['today_suspended'],
                    'today_event'     => $overview['today_event'],
                    'events'          => AcademicCalendarResource::collection($overview['events']),
                ],
            ]);
        }

        $events = $this->calendarService->listAllEvents();

        return ApiResponse::list($events, AcademicCalendarResource::class);
    }

    public function store(StoreAcademicCalendarRequest $request): JsonResponse
    {
        $created = $this->calendarService->createEvent($request->validated());

        return ApiResponse::item(new AcademicCalendarResource($created), 201);
    }

    public function destroy(Request $request, AcademicCalendar $academicCalendar): JsonResponse
    {
        $this->calendarService->deleteEvent($academicCalendar);

        return response()->json([
            'message' => 'Kalender Akademik berhasil dihapus.',
        ]);
    }
}
