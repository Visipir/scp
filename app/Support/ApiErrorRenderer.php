<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Renders every api/* failure into the uniform error envelope.
 */
class ApiErrorRenderer
{
    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return ApiResponse::message(
                $e->getMessage() ?: 'Data yang diberikan tidak valid.',
                422,
                $e->errors(),
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::message($e->getMessage() ?: 'Unauthenticated.', 401);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ApiResponse::message('Data tidak ditemukan.', 404);
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::message($e->getMessage() ?: 'Akses ditolak.', 403);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponse::message('Terlalu banyak percobaan. Silakan coba lagi nanti.', 429);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return ApiResponse::message($e->getMessage() ?: self::defaultMessage($status), $status);
        }

        return ApiResponse::message(
            config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
            500,
        );
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            401 => 'Unauthenticated.',
            403 => 'Akses ditolak.',
            404 => 'Data tidak ditemukan.',
            429 => 'Terlalu banyak percobaan. Silakan coba lagi nanti.',
            default => 'Terjadi kesalahan pada server.',
        };
    }
}
