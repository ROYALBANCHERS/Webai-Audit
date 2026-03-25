#!/usr/bin/env php
<?php
/**
 * Test Subscription API
 */

echo "=== Testing Subscription API ===\n\n";
$baseUrl = 'http://localhost:8000/api';

// Initialize cURL with session cookie
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
$ch = curl_init();

// Test 1: Get Plans
echo "1. Testing GET /subscription/plans...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo "✓ Found " . count($data['data']) . " subscription plans\n";
    foreach ($data['data'] as $plan) {
        echo "  - {$plan['name']}: {$plan['price']} ({$plan['credits']} credits/month)\n";
    }
} else {
    echo "✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Test 2: Get Current Subscription
echo "2. Testing GET /subscription/current...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/current');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo "✓ Current subscription loaded\n";
    $credits = $data['data']['credits_remaining'] ?? 0;
    $plan = $data['data']['plan']['name'] ?? 'Free';
    echo "  - Plan: $plan\n";
    echo "  - Credits: $credits\n";
} else {
    echo "✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Test 3: Get Usage Stats
echo "3. Testing GET /subscription/usage...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/usage');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo "✓ Usage stats loaded\n";
    $usage = $data['data'];
    echo "  - Max pages: {$usage['max_pages']}\n";
    echo "  - Max websites: {$usage['max_websites']}\n";
    echo "  - Competitors: " . ($usage['can_analyze_competitors'] ? 'Yes' : 'No') . "\n";
    echo "  - GitHub: " . ($usage['can_use_github'] ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Test 4: Get Feature Comparison
echo "4. Testing GET /subscription/compare...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/compare');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo "✓ Feature comparison loaded\n";
    echo "  - Found " . count($data['data']) . " plans\n";
} else {
    echo "✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Test 5: Subscribe to a plan (Starter)
echo "5. Testing POST /subscription/subscribe (Starter plan)...\n";
$planId = 2; // Starter plan
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/subscribe');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['plan_id' => $planId]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo "✓ Successfully subscribed to Starter plan\n";
    echo "  - " . $data['data']['message'] . "\n";
} else {
    echo "✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Verify subscription changed
echo "6. Verifying subscription change...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/subscription/current');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
$response = curl_exec($ch);
$data = json_decode($response, true);
$plan = $data['data']['plan']['name'] ?? 'Unknown';
$credits = $data['data']['credits_remaining'] ?? 0;
echo "  - Current Plan: $plan\n";
echo "  - Credits: $credits\n";
echo "\n";

curl_close($ch);
unlink($cookieFile);

echo "=== Subscription API Test Complete ===\n";
