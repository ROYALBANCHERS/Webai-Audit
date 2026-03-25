<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Secret Middleware
 * Validates X-Admin-Secret header against environment variable
 * Provides additional security layer for admin operations
 */
class AdminSecretMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminSecret = $request->header('X-Admin-Secret');
        $expectedSecret = config('app.admin_secret') ?? env('ADMIN_SECRET');

        // If admin secret is configured, validate it
        if ($expectedSecret && $adminSecret !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - invalid admin credentials'
            ], 401);
        }

        // If admin secret is not configured in production, deny access
        if (app()->environment('production') && empty($expectedSecret)) {
            \Illuminate\Support\Facades\Log::warning('Admin operation attempted without ADMIN_SECRET configured');
            return response()->json([
                'success' => false,
                'message' => 'Server configuration error'
            ], 500);
        }

        return $next($request);
    }
}
