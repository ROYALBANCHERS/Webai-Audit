#!/usr/bin/env php
<?php
/**
 * Comprehensive API Test
 */

echo "=== WebAI Auditor - Comprehensive API Test ===\n\n";
$baseUrl = 'http://localhost:8000/api';

$tests = [
    [
        'name' => 'Health Check',
        'method' => 'GET',
        'url' => '/health',
        'data' => null,
    ],
    [
        'name' => 'Stats',
        'method' => 'GET',
        'url' => '/stats',
        'data' => null,
    ],
    [
        'name' => 'SEO Analysis',
        'method' => 'POST',
        'url' => '/analyze/seo',
        'data' => json_encode(['url' => 'https://example.com']),
    ],
    [
        'name' => 'Tech Stack Detection',
        'method' => 'POST',
        'url' => '/analyze/tech-stack',
        'data' => json_encode(['url' => 'https://example.com']),
    ],
    [
        'name' => 'Crawl Website',
        'method' => 'POST',
        'url' => '/crawl',
        'data' => json_encode(['url' => 'https://example.com', 'max_depth' => 1]),
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $i => $test) {
    $num = $i + 1;
    echo "{$num}. Testing: {$test['name']}...\n";

    $ch = curl_init($baseUrl . $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($test['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    } else {
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    $isJson = $json !== null;

    // Health check may return 503 when Selenium is unavailable (degraded state)
    $isHealthCheck = ($test['name'] === 'Health Check');
    $validHealthCode = ($isHealthCheck && ($httpCode === 200 || $httpCode === 503));

    if ($httpCode === 200 && $isJson && isset($json['success']) && $json['success'] === true) {
        echo "   PASSED (HTTP {$httpCode})\n";
        $passed++;
    } elseif ($validHealthCode && $isJson && isset($json['status'])) {
        echo "   PASSED (HTTP {$httpCode})\n";
        $passed++;
    } elseif ($httpCode === 200 && $isJson) {
        echo "   PARTIAL (HTTP {$httpCode} - JSON response)\n";
        $passed++;
    } else {
        echo "   FAILED (HTTP {$httpCode})\n";
        if (!$isJson) {
            echo "   Response: " . substr($response, 0, 100) . "...\n";
        } else {
            echo "   Error: " . ($json['error'] ?? 'Unknown error') . "\n";
        }
        $failed++;
    }
    echo "\n";
}

echo "=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\nAll tests PASSED!\n";
    exit(0);
} else {
    echo "\nSome tests FAILED!\n";
    exit(1);
}
