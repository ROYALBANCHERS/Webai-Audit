<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\Cors::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'admin.secret' => \App\Http\Middleware\AdminSecretMiddleware::class,
            'admin' => \App\Http\Middleware\AdminSecretMiddleware::class,
        ]);

        // Configure rate limiting
        $middleware->limiting(
            authenticated: 'api',
            guests: 'guest'
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling for API
        $exceptions->render(function (Throwable $e, Request $request) {
            // Only handle API routes
            if (!$request->is('api/*')) {
                return null;
            }

            // Use the ApiResponse helper for consistent error responses
            $apiResponse = new \App\Helpers\ApiResponse();

            // Handle specific exceptions
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return \App\Helpers\ApiResponse::validationError(
                    $e->errors(),
                    $e->getMessage()
                );
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return \App\Helpers\ApiResponse::notFound(
                    'The requested resource was not found'
                );
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return \App\Helpers\ApiResponse::unauthorized(
                    'Authentication required'
                );
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return \App\Helpers\ApiResponse::forbidden(
                    'You do not have permission to perform this action'
                );
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return \App\Helpers\ApiResponse::notFound(
                    'API endpoint not found'
                );
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return \App\Helpers\ApiResponse::error(
                    'HTTP method not allowed',
                    405,
                    null,
                    'METHOD_NOT_ALLOWED'
                );
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                return \App\Helpers\ApiResponse::rateLimit(
                    $e->getHeaders() ? $e->getHeaders()['Retry-After'] ?? 60 : 60
                );
            }

            // Log the error
            \Illuminate\Support\Facades\Log::error('API Exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);

            // Generic server error
            return \App\Helpers\ApiResponse::serverError(
                app()->environment('production')
                    ? 'An unexpected error occurred. Please try again.'
                    : $e->getMessage(),
                $e
            );
        });
    })->create();
