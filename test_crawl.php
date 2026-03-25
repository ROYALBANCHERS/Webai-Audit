#!/usr/bin/env php
<?php
/**
 * Test Crawl Endpoint
 */

echo "=== Testing Crawl Endpoint ===\n\n";
$baseUrl = 'http://localhost:8000/api';

echo "1. Testing Crawl with example.com...\n";
$data = json_encode(['url' => 'https://example.com', 'max_depth' => 1]);
$ch = curl_init($baseUrl . '/crawl');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 800) . "\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['success']) && $result['success']) {
        echo "SUCCESS: Crawl completed!\n";
        echo "Pages crawled: " . ($result['pages_crawled'] ?? 'N/A') . "\n";
        echo "Load time: " . ($result['total_time'] ?? 'N/A') . "s\n";
    } else {
        echo "FAILED: Crawl returned success=false\n";
    }
} else {
    echo "FAILED: HTTP $httpCode\n";
}

echo "\n=== Test Complete ===\n";

