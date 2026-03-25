<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Activity Logger
 * Provides structured logging for application events
 */
class ActivityLog
{
    /**
     * Log an audit-related activity
     */
    public static function audit(string $action, array $context = []): void
    {
        self::log('audit', $action, $context);
    }

    /**
     * Log an API request
     */
    public static function apiRequest(string $endpoint, array $context = []): void
    {
        self::log('api', "API Request: {$endpoint", array_merge($context, [
            'endpoint' => $endpoint,
            'method' => Request::method(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]));
    }

    /**
     * Log an authentication event
     */
    public static function auth(string $action, ?int $userId = null, array $context = []): void
    {
        self::log('auth', $action, array_merge($context, [
            'user_id' => $userId,
            'ip' => Request::ip(),
        ]));
    }

    /**
     * Log a crawl event
     */
    public static function crawl(string $source, int $jobsAdded = 0, array $context = []): void
    {
        self::log('crawl', "Crawl completed for: {$source}", array_merge($context, [
            'source' => $source,
            'jobs_added' => $jobsAdded,
        ]));
    }

    /**
     * Log an error
     */
    public static function error(string $message, array $context = []): void
    {
        Log::error($message, array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
        ]));
    }

    /**
     * Log a security event
     */
    public static function security(string $event, array $context = []): void
    {
        Log::warning('[SECURITY] ' . $event, array_merge($context, [
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
        ]));
    }

    /**
     * Generic log method
     */
    protected static function log(string $channel, string $message, array $context = []): void
    {
        $logData = array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'user_id' => auth()->id(),
        ]);

        // Use different log levels based on severity
        $level = match($channel) {
            'audit', 'api' => 'info',
            'auth' => 'notice',
            'crawl' => 'info',
            'security' => 'warning',
            default => 'info',
        };

        Log::channel($channel)->$level($message, $logData);
    }
}

/**
 * Performance Monitor
 * Track performance metrics
 */
class PerformanceMonitor
{
    private static array $metrics = [];

    /**
     * Start timing an operation
     */
    public static function start(string $operation): string
    {
        $id = uniqid($operation . '_');
        self::$metrics[$id] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
        ];

        return $id;
    }

    /**
     * End timing an operation
     */
    public static function end(string $id): array
    {
        if (!isset(self::$metrics[$id])) {
            return [];
        }

        $metric = self::$metrics[$id];
        $duration = (microtime(true) - $metric['start_time']) * 1000; // ms
        $memoryUsed = memory_get_usage() - $metric['start_memory'];

        $result = [
            'operation' => $metric['operation'],
            'duration_ms' => round($duration, 2),
            'memory_bytes' => $memoryUsed,
            'memory_kb' => round($memoryUsed / 1024, 2),
        ];

        // Log slow operations
        if ($duration > 1000) {
            Log::warning('Slow operation detected', $result);
        }

        unset(self::$metrics[$id]);

        return $result;
    }

    /**
     * Log database query performance
     */
    public static function logQuery(string $sql, float $duration): void
    {
        if ($duration > 100) { // Log queries taking more than 100ms
            Log::warning('Slow database query', [
                'sql' => $sql,
                'duration_ms' => round($duration, 2),
            ]);
        }
    }
}
