#!/usr/bin/env php
<?php
/**
 * Test Tech Stack Detection
 */

echo "=== Testing Tech Stack Detection ===\n\n";
$baseUrl = 'http://localhost:8000/api';

echo "1. Testing Tech Stack on example.com...\n";
$data = json_encode(['url' => 'https://example.com']);
$ch = curl_init($baseUrl . '/analyze/tech-stack');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 1000) . "\n\n";

echo "2. Testing Tech Stack on github.com...\n";
$data = json_encode(['url' => 'https://github.com']);
$ch = curl_init($baseUrl . '/analyze/tech-stack');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 1000) . "\n\n";

echo "=== Test Complete ===\n";

