<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Helpers that enforce the uniform v1 JSON envelope.
 */
class ApiResponse
{
    public static function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public static function item(JsonResource $resource, int $status = 200): JsonResponse
    {
        return self::data($resource->resolve(), $status);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        $items = collect($paginator->items())
            ->map(fn ($model) => (new $resourceClass($model))->resolve())
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public static function message(string $message, int $status = 200, ?array $errors = null, ?string $code = null): JsonResponse
    {
        $payload = [
            'message' => $message,
            'errors'  => $errors,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }
}
