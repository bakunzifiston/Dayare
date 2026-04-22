<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class ApiJson
{
    /**
     * @param  mixed  $data  Resource payload; use paginator helper for paged lists. Pass null for empty object.
     */
    public static function success(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        if ($data === null) {
            $data = new \stdClass;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'OK', array $filters = []): JsonResponse
    {
        return self::success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => $filters === [] ? new \stdClass : $filters,
        ], $message);
    }

    /**
     * @param  array<string, mixed>  $errors  Validation-style field map, or empty for none.
     */
    public static function failure(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? new \stdClass : $errors,
        ], $status);
    }

    public static function fromValidationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }
}
