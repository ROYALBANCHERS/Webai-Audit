<?php

namespace App\Helpers;

/**
 * Input Sanitization Helper
 * Provides methods to sanitize and validate user input
 */
class Sanitizer
{
    /**
     * Sanitize a string input
     */
    public static function string(?string $input, int $maxLength = null): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove HTML tags
        $cleaned = strip_tags($input);

        // Trim whitespace
        $cleaned = trim($cleaned);

        // Remove control characters
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned);

        // Apply max length if specified
        if ($maxLength !== null && strlen($cleaned) > $maxLength) {
            $cleaned = substr($cleaned, 0, $maxLength);
        }

        return $cleaned ?: null;
    }

    /**
     * Sanitize an array of strings
     */
    public static function stringArray(array $input, int $maxLength = null): array
    {
        return array_filter(array_map(function ($item) use ($maxLength) {
            return self::string($item, $maxLength);
        }, $input), function ($item) {
            return $item !== null;
        });
    }

    /**
     * Sanitize a URL
     */
    public static function url(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Limit URL length
        if (strlen($url) > 2048) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize an email
     */
    public static function email(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($email);
    }

    /**
     * Sanitize HTML (allow certain safe tags)
     */
    public static function html(?string $html, array $allowedTags = []): ?string
    {
        if ($html === null) {
            return null;
        }

        // Strip all tags except allowed ones
        $cleaned = strip_tags($html, implode('', $allowedTags));

        // Remove attributes from remaining tags (security measure)
        $cleaned = preg_replace('/<([a-z]+)[^>]*>/i', '<$1>', $cleaned);

        return trim($cleaned) ?: null;
    }

    /**
     * Clean SQL-like patterns to prevent injection
     */
    public static function sqlPattern(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove common SQL injection patterns
        $patterns = [
            "/(\bunion\b.*\bselect\b)/i",
            "/(\bselect\b.*\bfrom\b)/i",
            "/(\binsert\b.*\binto\b)/i",
            "/(\bupdate\b.*\bset\b)/i",
            "/(\bdelete\b.*\bfrom\b)/i",
            "/(\bdrop\b.*\btable\b)/i",
            "/(;.*\b(exec|execute)\b)/i",
            "/(\bor\b.*=.*\bor\b)/i",
            "/(\band\b.*=.*\band\b)/i",
        ];

        $cleaned = preg_replace($patterns, '', $input);

        return $cleaned ?: null;
    }

    /**
     * Validate and sanitize date input
     */
    public static function date(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);
        $parsed = date_parse($date);

        if ($parsed['error_count'] > 0 || $parsed['warning_count'] > 0) {
            return null;
        }

        return date('Y-m-d', strtotime($date));
    }

    /**
     * Sanitize integer input
     */
    public static function int($input, int $min = null, int $max = null): ?int
    {
        $cleaned = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        $cleaned = (int) $cleaned;

        if ($min !== null && $cleaned < $min) {
            return $min;
        }

        if ($max !== null && $cleaned > $max) {
            return $max;
        }

        return $cleaned;
    }

    /**
     * Escape JSON output
     */
    public static function jsonEncode($data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
}
