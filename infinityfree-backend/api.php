<?php
/**
 * WebAI Auditor Backend API
 * Simple PHP API for InfinityFree Hosting
 */

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get requested path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if exists
$requestPath = str_replace('/api', '', $requestPath);

// Route the request
switch ($requestPath) {
    case '/health':
    case '':
        healthCheck();
        break;

    case '/audit':
        runAudit();
        break;

    case '/analyze/seo':
        analyzeSeo();
        break;

    case '/analyze/tech-stack':
        analyzeTechStack();
        break;

    case '/stats':
        getStats();
        break;

    default:
        sendResponse(['error' => 'Endpoint not found'], 404);
}

/**
 * Health Check Endpoint
 */
function healthCheck() {
    sendResponse([
        'status' => 'healthy',
        'service' => 'WebAI Auditor API',
        'version' => '1.0.0',
        'timestamp' => date('c')
    ]);
}

/**
 * Run Website Audit
 */
function runAudit() {
    $url = $_POST['url'] ?? $_GET['url'] ?? '';

    if (empty($url)) {
        sendResponse(['error' => 'URL is required'], 400);
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        sendResponse(['error' => 'Invalid URL'], 400);
    }

    // Analyze the website
    $results = analyzeWebsite($url);

    sendResponse([
        'success' => true,
        'url' => $url,
        'audit_id' => uniqid('audit_'),
        'timestamp' => date('c'),
        'results' => $results
    ]);
}

/**
 * Analyze SEO
 */
function analyzeSeo() {
    $url = $_POST['url'] ?? $_GET['url'] ?? '';

    if (empty($url)) {
        sendResponse(['error' => 'URL is required'], 400);
    }

    $html = fetchUrl($url);
    if ($html === false) {
        sendResponse(['error' => 'Failed to fetch URL'], 500);
    }

    $seoAnalysis = performSeoAnalysis($html, $url);

    sendResponse([
        'success' => true,
        'url' => $url,
        'seo_score' => $seoAnalysis['score'],
        'checks' => $seoAnalysis['checks'],
        'recommendations' => $seoAnalysis['recommendations']
    ]);
}

/**
 * Analyze Tech Stack
 */
function analyzeTechStack() {
    $url = $_POST['url'] ?? $_GET['url'] ?? '';

    if (empty($url)) {
        sendResponse(['error' => 'URL is required'], 400);
    }

    $html = fetchUrl($url);
    if ($html === false) {
        sendResponse(['error' => 'Failed to fetch URL'], 500);
    }

    $techStack = detectTechStack($html, $url);

    sendResponse([
        'success' => true,
        'url' => $url,
        'tech_stack' => $techStack
    ]);
}

/**
 * Get Statistics
 */
function getStats() {
    sendResponse([
        'total_audits' => 15234,
        'websites_analyzed' => 8547,
        'issues_found' => 45234,
        'happy_users' => 5023
    ]);
}

/**
 * Analyze Website
 */
function analyzeWebsite($url) {
    $html = fetchUrl($url);
    if ($html === false) {
        return ['error' => 'Failed to fetch website'];
    }

    $startTime = microtime(true);

    return [
        'url' => $url,
        'status_code' => 200,
        'title' => extractTitle($html),
        'meta_description' => extractMetaDescription($html),
        'tech_stack' => detectTechStack($html, $url),
        'seo' => performSeoAnalysis($html, $url),
        'load_time' => round(microtime(true) - $startTime, 2),
        'timestamp' => date('c')
    ];
}

/**
 * Fetch URL Content
 */
function fetchUrl($url) {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (WebAI Auditor)\r\n",
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);

    return $html;
}

/**
 * Extract Page Title
 */
function extractTitle($html) {
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

/**
 * Extract Meta Description
 */
function extractMetaDescription($html) {
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

/**
 * Perform SEO Analysis
 */
function performSeoAnalysis($html, $url) {
    $checks = [];
    $score = 0;
    $maxScore = 0;

    // Title Check
    $title = extractTitle($html);
    $titleLength = strlen($title);
    $maxScore += 15;
    if ($title && $titleLength >= 30 && $titleLength <= 60) {
        $score += 15;
        $checks['title'] = ['status' => 'pass', 'message' => 'Title is optimal'];
    } elseif ($title) {
        $score += 8;
        $checks['title'] = ['status' => 'warning', 'message' => 'Title length needs improvement'];
    } else {
        $checks['title'] = ['status' => 'fail', 'message' => 'Missing title tag'];
    }

    // Meta Description Check
    $metaDesc = extractMetaDescription($html);
    $metaDescLength = strlen($metaDesc);
    $maxScore += 15;
    if ($metaDesc && $metaDescLength >= 120 && $metaDescLength <= 160) {
        $score += 15;
        $checks['meta_description'] = ['status' => 'pass', 'message' => 'Meta description is optimal'];
    } elseif ($metaDesc) {
        $score += 8;
        $checks['meta_description'] = ['status' => 'warning', 'message' => 'Meta description length needs improvement'];
    } else {
        $checks['meta_description'] = ['status' => 'fail', 'message' => 'Missing meta description'];
    }

    // H1 Check
    $h1Count = preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches);
    $maxScore += 15;
    if ($h1Count === 1) {
        $score += 15;
        $checks['headings'] = ['status' => 'pass', 'message' => 'Proper H1 usage'];
    } elseif ($h1Count > 1) {
        $score += 8;
        $checks['headings'] = ['status' => 'warning', 'message' => 'Multiple H1 tags found'];
    } else {
        $checks['headings'] = ['status' => 'fail', 'message' => 'Missing H1 tag'];
    }

    // SSL Check
    $maxScore += 10;
    if (strpos($url, 'https://') === 0) {
        $score += 10;
        $checks['ssl'] = ['status' => 'pass', 'message' => 'HTTPS enabled'];
    } else {
        $checks['ssl'] = ['status' => 'fail', 'message' => 'Not using HTTPS'];
    }

    // Image Alt Check
    $imgCount = preg_match_all('/<img[^>]*>/is', $html, $imgMatches);
    $altCount = preg_match_all('/<img[^>]*alt=[^>]*>/is', $html, $altMatches);
    $maxScore += 10;
    if ($imgCount > 0 && $altCount === $imgCount) {
        $score += 10;
        $checks['images_alt'] = ['status' => 'pass', 'message' => 'All images have alt text'];
    } elseif ($imgCount > 0) {
        $partialScore = round(($altCount / $imgCount) * 10);
        $score += $partialScore;
        $checks['images_alt'] = ['status' => 'warning', 'message' => "$altCount of $imgCount images have alt text"];
    } else {
        $checks['images_alt'] = ['status' => 'pass', 'message' => 'No images found'];
        $score += 10;
    }

    // Mobile Viewport Check
    $maxScore += 10;
    if (preg_match('/<meta\s+name=["\']viewport["\']/is', $html)) {
        $score += 10;
        $checks['viewport'] = ['status' => 'pass', 'message' => 'Viewport meta tag present'];
    } else {
        $checks['viewport'] = ['status' => 'fail', 'message' => 'Missing viewport meta tag'];
    }

    // Canonical Check
    $maxScore += 10;
    if (preg_match('/<link\s+rel=["\']canonical["\']/is', $html)) {
        $score += 10;
        $checks['canonical'] = ['status' => 'pass', 'message' => 'Canonical tag present'];
    } else {
        $score += 5;
        $checks['canonical'] = ['status' => 'warning', 'message' => 'No canonical tag'];
    }

    // Sitemap Check (simplified)
    $maxScore += 5;
    $parsedUrl = parse_url($url);
    $sitemapUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/sitemap.xml';
    $sitemapExists = @file_get_contents($sitemapUrl);
    if ($sitemapExists) {
        $score += 5;
        $checks['sitemap'] = ['status' => 'pass', 'message' => 'Sitemap found'];
    } else {
        $checks['sitemap'] = ['status' => 'warning', 'message' => 'Sitemap not detected'];
    }

    // Calculate final score
    $finalScore = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;

    return [
        'score' => min(100, $finalScore),
        'checks' => $checks,
        'recommendations' => generateRecommendations($checks)
    ];
}

/**
 * Detect Tech Stack
 */
function detectTechStack($html, $url) {
    $tech = [
        'frameworks' => [],
        'libraries' => [],
        'cms' => [],
        'analytics' => [],
        'fonts' => []
    ];

    $htmlLower = strtolower($html);

    // Frameworks
    $frameworks = [
        'React' => ['react', 'reactjs', 'react-dom'],
        'Vue' => ['vue', 'vuejs', 'nuxt'],
        'Angular' => ['angular', 'ng-app'],
        'Next.js' => ['next.js', '__next'],
        'Laravel' => ['laravel', 'csrf-token'],
        'WordPress' => ['wp-content', 'wordpress'],
        'Shopify' => ['shopify', 'cdn.shopify'],
        'Bootstrap' => ['bootstrap'],
        'Tailwind' => ['tailwind']
    ];

    foreach ($frameworks as $name => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($htmlLower, $pattern) !== false) {
                $tech['frameworks'][] = $name;
                break;
            }
        }
    }

    // Analytics
    if (strpos($htmlLower, 'google-analytics') !== false ||
        strpos($htmlLower, 'googletagmanager') !== false) {
        $tech['analytics'][] = 'Google Analytics';
    }
    if (strpos($htmlLower, 'facebook.com') !== false) {
        $tech['analytics'][] = 'Facebook Pixel';
    }
    if (strpos($htmlLower, 'hotjar') !== false) {
        $tech['analytics'][] = 'Hotjar';
    }

    // Fonts
    if (strpos($htmlLower, 'fonts.googleapis') !== false) {
        $tech['fonts'][] = 'Google Fonts';
    }
    if (strpos($htmlLower, 'fontawesome') !== false) {
        $tech['fonts'][] = 'Font Awesome';
    }

    // CMS
    if (strpos($htmlLower, 'wp-content') !== false) {
        $tech['cms'][] = 'WordPress';
    }
    if (strpos($htmlLower, 'shopify') !== false) {
        $tech['cms'][] = 'Shopify';
    }
    if (strpos($htmlLower, 'joomla') !== false) {
        $tech['cms'][] = 'Joomla';
    }
    if (strpos($htmlLower, 'drupal') !== false) {
        $tech['cms'][] = 'Drupal';
    }

    return $tech;
}

/**
 * Generate SEO Recommendations
 */
function generateRecommendations($checks) {
    $recommendations = [];

    foreach ($checks as $check => $result) {
        if ($result['status'] === 'fail' || $result['status'] === 'warning') {
            $recommendations[] = [
                'check' => $check,
                'message' => $result['message'],
                'priority' => $result['status'] === 'fail' ? 'high' : 'medium'
            ];
        }
    }

    return $recommendations;
}

/**
 * Send JSON Response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
