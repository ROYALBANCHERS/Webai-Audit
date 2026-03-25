#!/usr/bin/env php
<?php
/**
 * Test Full Website Analysis (simulating browser behavior)
 */

echo "=== Testing Full Website Analysis ===\n\n";
$baseUrl = 'http://localhost:8000/api';

$testUrl = 'https://example.com';

echo "Testing URL: $testUrl\n\n";

// Step 1: Check subscription and credits
echo "1. Checking subscription/credits...\n";
$ch = curl_init($baseUrl . '/subscription/usage');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
echo "   Max Pages: " . ($data['data']['max_pages'] ?? 'N/A') . "\n";
echo "   Credits Remaining: " . ($data['data']['credits_remaining'] ?? 'N/A') . "\n\n";

// Step 2: Analyze Tech Stack
echo "2. Analyzing Tech Stack...\n";
$ch = curl_init($baseUrl . '/analyze/tech-stack');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $testUrl]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP Code: $httpCode\n";
$techData = json_decode($response, true);
if ($httpCode === 200 && $techData['success']) {
    $techCount = count($techData['data']['all'] ?? []);
    echo "   ✓ Detected $techCount technologies\n";
    foreach (array_slice($techData['data']['all'] ?? [], 0, 5) as $tech) {
        echo "     - {$tech['name']} ({$tech['category']})\n";
    }
} else {
    echo "   ✗ Failed\n";
}
echo "\n";

// Step 3: Analyze SEO
echo "3. Analyzing SEO...\n";
$ch = curl_init($baseUrl . '/analyze/seo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $testUrl]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP Code: $httpCode\n";
$seoData = json_decode($response, true);
if ($httpCode === 200 && $seoData['success']) {
    echo "   ✓ SEO Score: " . ($seoData['data']['score'] ?? 'N/A') . "\n";
    echo "   ✓ Title: " . ($seoData['data']['checks']['title']['content'] ?? 'N/A') . "\n";
    echo "   ✓ H1 Count: " . ($seoData['data']['checks']['headings']['h1_count'] ?? 'N/A') . "\n";
} else {
    echo "   ✗ Failed\n";
}
echo "\n";

// Step 4: Crawl (multi-page)
echo "4. Crawling Website (multi-page)...\n";
$ch = curl_init($baseUrl . '/crawl');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $testUrl, 'max_depth' => 2, 'max_pages' => 10]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP Code: $httpCode\n";
$crawlData = json_decode($response, true);
if ($httpCode === 200 && $crawlData['success']) {
    echo "   ✓ Pages Crawled: " . ($crawlData['data']['pages_crawled'] ?? 'N/A') . "\n";
    echo "   ✓ Load Time: " . ($crawlData['data']['total_time'] ?? 'N/A') . "s\n";
    $issues = count($crawlData['data']['issues'] ?? []);
    echo "   ✓ Issues Found: $issues\n";
} else {
    echo "   ✗ Failed: " . substr($response, 0, 200) . "\n";
}
echo "\n";

echo "=== Analysis Complete ===\n";
echo "\nYou can now test the same in your browser at: http://localhost:8080/index.html\n";
echo "Enter URL: $testUrl\n";
echo "Click Options to configure max pages/depth\n";
echo "Click Analyze to see full results\n";
