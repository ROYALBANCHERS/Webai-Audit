<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

/**
 * Standardized API Response Helper
 * Provides consistent response format for all API endpoints
 */
class ApiResponse
{
    /**
     * Success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        mixed $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return self::error($message, 404, null, 'NOT_FOUND');
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return self::error($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return self::error($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Server error response
     */
    public static function serverError(
        string $message = 'Internal server error',
        ?Throwable $exception = null
    ): JsonResponse {
        $data = null;

        // Include exception details in development mode only
        if (app()->environment('local', 'development') && $exception) {
            $data = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return self::error($message, 500, $data, 'SERVER_ERROR');
    }

    /**
     * Created response
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * No content response
     */
    public static function noContent(): JsonResponse {
        return response()->json(null, 204);
    }

    /**
     * Paginated response
     */
    public static function paginated(
        $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    /**
     * Rate limited response
     */
    public static function rateLimit(
        int $retryAfter = null
    ): JsonResponse {
        $headers = [];
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'timestamp' => now()->toIso8601String(),
        ], 429, $headers);
    }
}
