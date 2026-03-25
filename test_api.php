#!/usr/bin/env php
<?php
/**
 * Simple Test Script for WebAI Auditor API
 */

echo "=== WebAI Auditor API Test ===\n\n";
$baseUrl = 'http://localhost:8000/api';

// Test 1: Health Check
echo "1. Testing Health Endpoint...\n";
$ch = curl_init($baseUrl . '/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);
echo "Response: " . substr($response, 0, 200) . "\n\n";

// Test 2: Stats
echo "2. Testing Stats Endpoint...\n";
$ch = curl_init($baseUrl . '/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);
echo "Response: " . substr($response, 0, 200) . "\n\n";

// Test 3: SEO Analysis
echo "3. Testing SEO Analysis...\n";
$data = json_encode(['url' => 'https://example.com']);
$ch = curl_init($baseUrl . '/analyze/seo');
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
echo "Response: " . substr($response, 0, 500) . "\n\n";

echo "=== Test Complete ===\n";
