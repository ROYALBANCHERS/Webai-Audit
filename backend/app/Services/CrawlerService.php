<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;
use DOMDocument;

class CrawlerService
{
    protected array $config;
    protected array $crawledUrls = [];
    protected int $maxDepth;
    protected int $timeout;
    protected array $issues = [];
    protected array $performanceMetrics = [];

    public function __construct()
    {
        $this->config = config('auditor.crawler') ?? [];
        $this->maxDepth = $this->config['max_depth'] ?? 3;
        $this->timeout = $this->config['timeout'] ?? 30;
    }

    /**
     * Initialize the WebDriver (not implemented - using HTTP-only mode)
     */
    protected function initDriver(): bool
    {
        // WebDriver not available - using HTTP-only mode
        return false;
    }

    /**
     * Main method to crawl a website
     */
    public function crawlSite(string $url, array $options = []): array
    {
        $this->crawledUrls = [];
        $this->issues = [];
        $this->performanceMetrics = [];

        $maxDepth = $options['max_depth'] ?? $this->maxDepth;
        $maxPages = $options['max_pages'] ?? config('auditor.crawler.max_pages', 50);

        $startTime = microtime(true);

        try {
            // Note: WebDriver mode not available - using HTTP-only mode
            $results = $this->crawlPage($url, 0, $maxDepth, $maxPages);

            $totalTime = microtime(true) - $startTime;

            return [
                'success' => true,
                'url' => $url,
                'pages_crawled' => count($this->crawledUrls),
                'total_time' => round($totalTime, 2),
                'pages' => $results,
                'issues' => $this->issues,
                'performance' => $this->performanceMetrics,
            ];
        } catch (\Exception $e) {
            Log::error("Crawling error: {$e->getMessage()}", ['url' => $url]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
                'pages_crawled' => count($this->crawledUrls),
            ];
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Crawl a single page recursively
     */
    protected function crawlPage(string $url, int $currentDepth, int $maxDepth, int &$maxPages): array
    {
        if ($maxPages <= 0 || $currentDepth > $maxDepth) {
            return [];
        }

        // Normalize URL
        $normalizedUrl = $this->normalizeUrl($url);
        $baseUrl = parse_url($normalizedUrl, PHP_URL_SCHEME) . '://' . parse_url($normalizedUrl, PHP_URL_HOST);

        if (in_array($normalizedUrl, $this->crawledUrls)) {
            return [];
        }

        if (!str_starts_with($normalizedUrl, $baseUrl)) {
            return []; // Skip external links
        }

        $this->crawledUrls[] = $normalizedUrl;
        $maxPages--;

        $startTime = microtime(true);

        try {
            $pageData = $this->analyzePage($normalizedUrl);
            $pageData['depth'] = $currentDepth;
            $pageData['load_time'] = microtime(true) - $startTime;

            $this->performanceMetrics[] = [
                'url' => $normalizedUrl,
                'load_time' => $pageData['load_time'],
                'status_code' => $pageData['status_code'] ?? 200,
            ];

            // Check links
            if (!empty($pageData['links'])) {
                $pageData['link_results'] = $this->checkLinks($pageData['links'], $baseUrl);
            }

            // Check buttons
            if (!empty($pageData['buttons'])) {
                $pageData['button_results'] = $this->testButtons($pageData['buttons']);
            }

            // Detect errors
            if (!empty($pageData['html'])) {
                $pageData['errors'] = $this->detectErrors($pageData['html']);
            }

            // Recursively crawl internal links
            if ($currentDepth < $maxDepth && $maxPages > 0) {
                $subPages = [];
                foreach (($pageData['internal_links'] ?? []) as $link) {
                    if ($maxPages <= 0) break;
                    $subPages[] = $this->crawlPage($link, $currentDepth + 1, $maxDepth, $maxPages);
                }
                $pageData['sub_pages'] = array_filter($subPages);
            }

            return $pageData;
        } catch (\Exception $e) {
            $this->issues[] = [
                'type' => 'crawler',
                'severity' => 'high',
                'message' => "Failed to crawl page: {$e->getMessage()}",
                'location' => $normalizedUrl,
            ];

            return [
                'url' => $normalizedUrl,
                'error' => $e->getMessage(),
                'depth' => $currentDepth,
            ];
        }
    }

    /**
     * Analyze a single page
     */
    public function analyzePage(string $url): array
    {
        $startTime = microtime(true);

        try {
            // First try with HTTP client for basic info
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => $this->config['user_agent']])
                ->withoutVerifying()
                ->get($url);

            $html = $response->body();
            $statusCode = $response->status();
            $headers = $response->headers();

            // Parse HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();

            $crawler = new SymfonyCrawler($html, $url);

            $result = [
                'url' => $url,
                'status_code' => $statusCode,
                'title' => $crawler->filter('title')->first()->text() ?? '',
                'meta_description' => $this->getMetaContent($crawler, 'description'),
                'meta_keywords' => $this->getMetaContent($crawler, 'keywords'),
                'html' => $html,
                'size' => strlen($html),
                'headers' => $headers,
            ];

            // Extract links
            $result['links'] = [];
            $result['internal_links'] = [];
            $result['external_links'] = [];

            $crawler->filter('a[href]')->each(function (SymfonyCrawler $node) use (&$result, $url) {
                $href = $node->attr('href');
                $absoluteUrl = $this->makeAbsolute($href, $url);
                $text = trim($node->text());

                $result['links'][] = [
                    'href' => $absoluteUrl,
                    'text' => $text,
                    'is_internal' => $this->isInternalLink($absoluteUrl, $url),
                ];

                if ($this->isInternalLink($absoluteUrl, $url)) {
                    $result['internal_links'][] = $absoluteUrl;
                } else {
                    $result['external_links'][] = $absoluteUrl;
                }
            });

            // Extract buttons
            $result['buttons'] = [];
            $crawler->filter('button, input[type="button"], input[type="submit"], [role="button"]')->each(function (SymfonyCrawler $node) use (&$result) {
                $result['buttons'][] = [
                    'tag' => $node->nodeName(),
                    'id' => $node->attr('id'),
                    'class' => $node->attr('class'),
                    'text' => trim($node->text()),
                ];
            });

            // Extract scripts
            $result['scripts'] = [];
            $crawler->filter('script[src]')->each(function (SymfonyCrawler $node) use (&$result) {
                $result['scripts'][] = $node->attr('src');
            });

            // Extract stylesheets
            $result['stylesheets'] = [];
            $crawler->filter('link[rel="stylesheet"]')->each(function (SymfonyCrawler $node) use (&$result) {
                $result['stylesheets'][] = $node->attr('href');
            });

            // Extract images
            $result['images'] = [];
            $crawler->filter('img')->each(function (SymfonyCrawler $node) use (&$result, $url) {
                $src = $node->attr('src');
                $result['images'][] = [
                    'src' => $this->makeAbsolute($src, $url),
                    'alt' => $node->attr('alt'),
                    'width' => $node->attr('width'),
                    'height' => $node->attr('height'),
                ];
            });

            // Extract headings
            $result['headings'] = [];
            foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
                $crawler->filter($tag)->each(function (SymfonyCrawler $node) use (&$result, $tag) {
                    $result['headings'][] = [
                        'tag' => $tag,
                        'text' => trim($node->text()),
                    ];
                });
            }

            $result['load_time'] = microtime(true) - $startTime;

            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Failed to analyze page: {$e->getMessage()}");
        }
    }

    /**
     * Capture screenshot of a page (not available without WebDriver)
     */
    public function captureScreenshot(string $url, string $outputPath): bool
    {
        // Screenshot functionality requires WebDriver
        Log::warning("Screenshot functionality requires php-webdriver/Selenium", ['url' => $url]);
        return false;
    }

    /**
     * Check links for broken URLs
     */
    public function checkLinks(array $links, string $baseUrl): array
    {
        $results = [];
        $checked = [];

        foreach ($links as $link) {
            $url = is_array($link) ? $link['href'] : $link;

            if (in_array($url, $checked)) {
                continue;
            }
            $checked[] = $url;

            try {
                $response = Http::timeout(10)->head($url);
                $statusCode = $response->status();

                $results[] = [
                    'url' => $url,
                    'status' => $statusCode,
                    'is_broken' => $statusCode >= 400,
                ];

                if ($statusCode >= 400) {
                    $this->issues[] = [
                        'type' => 'broken_link',
                        'severity' => $statusCode >= 500 ? 'high' : 'medium',
                        'message' => "Broken link found: HTTP {$statusCode}",
                        'location' => $url,
                        'recommendation' => 'Fix or remove the broken link.',
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'url' => $url,
                    'status' => 0,
                    'is_broken' => true,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Test interactive buttons
     */
    public function testButtons(array $buttons): array
    {
        $results = [];

        foreach ($buttons as $button) {
            $result = [
                'button' => $button,
                'has_text' => !empty($button['text']),
                'has_id' => !empty($button['id']),
                'has_class' => !empty($button['class']),
                'is_accessible' => !empty($button['text']) || !empty($button['id']) || !empty($button['aria-label']),
            ];

            if (!$result['is_accessible']) {
                $this->issues[] = [
                    'type' => 'accessibility',
                    'severity' => 'medium',
                    'message' => 'Button without accessible text found',
                    'location' => "Button: {$button['tag']}",
                    'recommendation' => 'Add aria-label or visible text to the button for accessibility.',
                ];
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Detect errors in HTML
     */
    public function detectErrors(string $html): array
    {
        $errors = [];

        // Check for common error indicators in HTML
        $errorPatterns = [
            'error' => '/<[^>]+error[^>]*>/i',
            'warning' => '/<[^>]+warning[^>]*>/i',
            'fatal' => '/<[^>]+fatal[^>]*>/i',
            'deprecated' => '/<[^>]+deprecated[^>]*>/i',
        ];

        foreach ($errorPatterns as $type => $pattern) {
            if (preg_match($pattern, $html)) {
                $errors[] = [
                    'type' => $type,
                    'message' => "Potential {$type} detected in HTML source",
                ];
            }
        }

        // Check for JavaScript errors (common patterns)
        if (preg_match('/console\.error\(/i', $html)) {
            $errors[] = [
                'type' => 'javascript',
                'message' => 'JavaScript console.error detected',
            ];
        }

        // Check for missing doctype
        if (!preg_match('/<!DOCTYPE/i', $html)) {
            $errors[] = [
                'type' => 'html',
                'message' => 'Missing DOCTYPE declaration',
                'severity' => 'low',
            ];
        }

        return $errors;
    }

    /**
     * Get page performance metrics (simplified HTTP-only version)
     */
    public function getPerformanceMetrics(string $url): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout($this->timeout)
                ->withoutVerifying()
                ->get($url);

            $loadTime = microtime(true) - $startTime;

            return [
                'page_load_time' => $loadTime,
                'status_code' => $response->status(),
                'size' => strlen($response->body()),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Helper: Normalize URL
     */
    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }

    /**
     * Helper: Make absolute URL
     */
    protected function makeAbsolute(string $href, string $baseUrl): string
    {
        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return parse_url($baseUrl, PHP_URL_SCHEME) . ':' . $href;
        }

        if (str_starts_with($href, '/')) {
            $base = parse_url($baseUrl);
            return $base['scheme'] . '://' . $base['host'] . $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }

    /**
     * Helper: Check if link is internal
     */
    protected function isInternalLink(string $url, string $baseUrl): bool
    {
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        return $urlHost === $baseHost || $urlHost === null;
    }

    /**
     * Helper: Get meta tag content
     */
    protected function getMetaContent(SymfonyCrawler $crawler, string $name): ?string
    {
        $node = $crawler->filterXPath("//meta[@name='{$name}']")->first();
        return $node->count() > 0 ? $node->attr('content') : null;
    }

    /**
     * Cleanup resources
     */
    protected function cleanup(): void
    {
        // No WebDriver resources to clean up
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
