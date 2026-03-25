<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;
use DOMDocument;

class SeoService
{
    protected array $weights;
    protected array $issues = [];
    protected array $warnings = [];
    protected array $passes = [];

    public function __construct()
    {
        $weights = config('auditor.seo_weights');
        $this->weights = is_array($weights) ? $weights : [
            'title' => 15,
            'meta_description' => 15,
            'headings' => 15,
            'images_alt' => 10,
            'canonical' => 10,
            'robots' => 5,
            'sitemap' => 5,
            'schema' => 10,
            'speed' => 10,
            'mobile' => 5,
            'ssl' => 10,
        ];
    }

    /**
     * Main method to analyze SEO
     */
    public function analyzeSeo(string $url, ?string $html = null): array
    {
        $this->issues = [];
        $this->warnings = [];
        $this->passes = [];

        if ($html === null) {
            $response = Http::timeout(30)->withoutVerifying()->get($url);
            $html = $response->body();
        }

        $crawler = new SymfonyCrawler($html, $url);

        $checks = [
            'title' => $this->checkTitle($crawler),
            'meta_description' => $this->checkMetaDescription($crawler),
            'headings' => $this->checkHeadings($crawler),
            'images_alt' => $this->checkImagesAlt($crawler),
            'canonical' => $this->checkCanonical($crawler),
            'robots' => $this->checkRobots($url),
            'sitemap' => $this->checkSitemap($url),
            'schema' => $this->checkSchema($crawler),
            'speed' => $this->checkSpeed($url, $html),
            'mobile' => $this->checkMobile($crawler),
            'ssl' => $this->checkSSL($url),
            'meta_viewport' => $this->checkMetaViewport($crawler),
            'open_graph' => $this->checkOpenGraph($crawler),
            'twitter_cards' => $this->checkTwitterCards($crawler),
            'favicon' => $this->checkFavicon($crawler),
            'language' => $this->checkLanguage($crawler),
            'internal_links' => $this->checkInternalLinks($crawler),
            'external_links' => $this->checkExternalLinks($crawler),
        ];

        $score = $this->calculateSeoScore($checks);

        return [
            'score' => $score,
            'checks' => $checks,
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'passes' => $this->passes,
            'recommendations' => $this->generateRecommendations($checks),
        ];
    }

    /**
     * Check title tag
     */
    protected function checkTitle(SymfonyCrawler $crawler): array
    {
        $titleNode = $crawler->filter('title')->first();
        $hasTitle = $titleNode->count() > 0;
        $title = $hasTitle ? trim($titleNode->text()) : '';
        $length = mb_strlen($title);

        $result = [
            'exists' => $hasTitle,
            'content' => $title,
            'length' => $length,
            'status' => 'pass',
            'message' => '',
        ];

        if (!$hasTitle) {
            $result['status'] = 'fail';
            $result['message'] = 'Missing title tag';
            $this->issues[] = [
                'type' => 'seo',
                'severity' => 'critical',
                'message' => 'Missing title tag',
                'location' => '<head>',
                'recommendation' => 'Add a descriptive title tag (50-60 characters recommended).',
            ];
        } elseif ($length < 30) {
            $result['status'] = 'warning';
            $result['message'] = 'Title is too short';
            $this->warnings[] = [
                'type' => 'seo',
                'message' => "Title is too short ({$length} characters). Recommended: 50-60 characters.",
                'recommendation' => 'Increase title length to 50-60 characters for better SEO.',
            ];
        } elseif ($length > 60) {
            $result['status'] = 'warning';
            $result['message'] = 'Title is too long';
            $this->warnings[] = [
                'type' => 'seo',
                'message' => "Title is too long ({$length} characters). Recommended: 50-60 characters.",
                'recommendation' => 'Shorten title to 50-60 characters to prevent truncation in search results.',
            ];
        } else {
            $this->passes[] = 'Title tag is present and properly sized';
        }

        return $result;
    }

    /**
     * Check meta description
     */
    protected function checkMetaDescription(SymfonyCrawler $crawler): array
    {
        $metaNode = $crawler->filterXPath('//meta[@name="description"]')->first();
        $hasMeta = $metaNode->count() > 0;
        $content = $hasMeta ? $metaNode->attr('content') : '';
        $length = mb_strlen($content);

        $result = [
            'exists' => $hasMeta,
            'content' => $content,
            'length' => $length,
            'status' => 'pass',
            'message' => '',
        ];

        if (!$hasMeta || empty($content)) {
            $result['status'] = 'fail';
            $result['message'] = 'Missing meta description';
            $this->issues[] = [
                'type' => 'seo',
                'severity' => 'high',
                'message' => 'Missing meta description',
                'location' => '<meta name="description">',
                'recommendation' => 'Add a meta description (150-160 characters recommended).',
            ];
        } elseif ($length < 120) {
            $result['status'] = 'warning';
            $result['message'] = 'Meta description is too short';
            $this->warnings[] = [
                'type' => 'seo',
                'message' => "Meta description is too short ({$length} characters). Recommended: 150-160 characters.",
                'recommendation' => 'Write a more descriptive meta description (150-160 characters).',
            ];
        } elseif ($length > 160) {
            $result['status'] = 'warning';
            $result['message'] = 'Meta description is too long';
            $this->warnings[] = [
                'type' => 'seo',
                'message' => "Meta description is too long ({$length} characters). Recommended: 150-160 characters.",
                'recommendation' => 'Shorten meta description to 150-160 characters.',
            ];
        } else {
            $this->passes[] = 'Meta description is present and properly sized';
        }

        return $result;
    }

    /**
     * Check heading structure
     */
    protected function checkHeadings(SymfonyCrawler $crawler): array
    {
        $headings = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
        ];

        foreach (array_keys($headings) as $tag) {
            $crawler->filter($tag)->each(function (SymfonyCrawler $node) use (&$headings, $tag) {
                $headings[$tag][] = trim($node->text());
            });
        }

        $h1Count = count($headings['h1']);
        $result = [
            'h1_count' => $h1Count,
            'h1s' => $headings['h1'],
            'h2_count' => count($headings['h2']),
            'h3_count' => count($headings['h3']),
            'total_headings' => array_sum(array_map('count', $headings)),
            'status' => 'pass',
            'message' => '',
        ];

        if ($h1Count === 0) {
            $result['status'] = 'fail';
            $result['message'] = 'Missing H1 tag';
            $this->issues[] = [
                'type' => 'seo',
                'severity' => 'critical',
                'message' => 'Missing H1 heading',
                'location' => '<body>',
                'recommendation' => 'Add exactly one H1 heading per page that includes your main keyword.',
            ];
        } elseif ($h1Count > 1) {
            $result['status'] = 'warning';
            $result['message'] = 'Multiple H1 tags found';
            $this->warnings[] = [
                'type' => 'seo',
                'message' => "Found {$h1Count} H1 tags. Recommended: 1 H1 per page.",
                'recommendation' => 'Use only one H1 tag per page. Use H2-H6 for subheadings.',
            ];
        } else {
            $this->passes[] = 'Proper H1 tag usage';
        }

        // Check heading hierarchy
        if ($result['total_headings'] > 0 && $h1Count > 0) {
            $hasHierarchy = true;
            $prevLevel = 1;

            $crawler->filter('h1, h2, h3, h4, h5, h6')->each(function (SymfonyCrawler $node) use (&$hasHierarchy, &$prevLevel) {
                $currentLevel = (int) str_replace('h', '', $node->nodeName());

                if ($currentLevel > $prevLevel + 1) {
                    $hasHierarchy = false;
                }

                $prevLevel = $currentLevel;
            });

            if (!$hasHierarchy) {
                $result['hierarchy'] = 'improper';
                $result['status'] = 'warning';
                $this->warnings[] = [
                    'type' => 'seo',
                    'message' => 'Heading hierarchy is improper (skipped levels)',
                    'recommendation' => 'Use headings in proper order (H1 → H2 → H3, etc.).',
                ];
            }
        }

        return $result;
    }

    /**
     * Check image alt attributes
     */
    protected function checkImagesAlt(SymfonyCrawler $crawler): array
    {
        $images = [];
        $imagesWithoutAlt = 0;
        $imagesWithEmptyAlt = 0;
        $totalImages = 0;

        $crawler->filter('img')->each(function (SymfonyCrawler $node) use (&$images, &$imagesWithoutAlt, &$imagesWithEmptyAlt, &$totalImages) {
            $totalImages++;
            $src = $node->attr('src');
            $alt = $node->attr('alt');

            $images[] = [
                'src' => $src,
                'has_alt' => $alt !== null,
                'alt_empty' => $alt === '',
                'alt' => $alt,
            ];

            if ($alt === null) {
                $imagesWithoutAlt++;
            } elseif (trim($alt) === '') {
                $imagesWithEmptyAlt++;
            }
        });

        $imagesMissingAlt = $imagesWithoutAlt + $imagesWithEmptyAlt;
        $percentage = $totalImages > 0 ? ($imagesMissingAlt / $totalImages) * 100 : 0;

        $result = [
            'total_images' => $totalImages,
            'without_alt' => $imagesWithoutAlt,
            'with_empty_alt' => $imagesWithEmptyAlt,
            'missing_alt_percentage' => round($percentage, 2),
            'status' => 'pass',
            'message' => '',
        ];

        if ($totalImages > 0) {
            if ($percentage > 50) {
                $result['status'] = 'fail';
                $result['message'] = "High percentage of images missing alt text ({$percentage}%)";
                $this->issues[] = [
                    'type' => 'accessibility',
                    'severity' => 'high',
                    'message' => "{$percentage}% of images are missing alt text",
                    'recommendation' => 'Add descriptive alt text to all images for accessibility and SEO.',
                ];
            } elseif ($percentage > 0) {
                $result['status'] = 'warning';
                $result['message'] = "Some images missing alt text ({$imagesMissingAlt} of {$totalImages})";
                $this->warnings[] = [
                    'type' => 'accessibility',
                    'message' => "{$imagesMissingAlt} of {$totalImages} images are missing alt text",
                    'recommendation' => 'Add alt text to remaining images.',
                ];
            } else {
                $this->passes[] = 'All images have alt attributes';
            }
        }

        return $result;
    }

    /**
     * Check canonical URL
     */
    protected function checkCanonical(SymfonyCrawler $crawler): array
    {
        $canonical = $crawler->filterXPath('//link[@rel="canonical"]')->first();
        $hasCanonical = $canonical->count() > 0;
        $href = $hasCanonical ? $canonical->attr('href') : null;

        $result = [
            'exists' => $hasCanonical,
            'href' => $href,
            'status' => $hasCanonical ? 'pass' : 'warning',
            'message' => $hasCanonical ? 'Canonical tag present' : 'Missing canonical tag',
        ];

        if (!$hasCanonical) {
            $this->warnings[] = [
                'type' => 'seo',
                'message' => 'No canonical tag found',
                'recommendation' => 'Add a canonical tag to prevent duplicate content issues.',
            ];
        } else {
            $this->passes[] = 'Canonical tag is present';
        }

        return $result;
    }

    /**
     * Check robots.txt
     */
    protected function checkRobots(string $url): array
    {
        $parsedUrl = parse_url($url);
        $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($robotsUrl);
            $exists = $response->successful();
            $content = $exists ? $response->body() : null;

            $result = [
                'exists' => $exists,
                'url' => $robotsUrl,
                'content' => $content ? substr($content, 0, 500) : null,
                'status' => 'pass',
                'message' => $exists ? 'robots.txt found' : 'robots.txt not found',
            ];

            if (!$exists) {
                $result['status'] = 'warning';
                $this->warnings[] = [
                    'type' => 'seo',
                    'message' => 'robots.txt not found',
                    'location' => $robotsUrl,
                    'recommendation' => 'Create a robots.txt file to control crawler access.',
                ];
            } else {
                $this->passes[] = 'robots.txt is accessible';
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'url' => $robotsUrl,
                'error' => $e->getMessage(),
                'status' => 'warning',
                'message' => 'Could not check robots.txt',
            ];
        }
    }

    /**
     * Check sitemap.xml
     */
    protected function checkSitemap(string $url): array
    {
        $parsedUrl = parse_url($url);
        $sitemapUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/sitemap.xml';

        try {
            $response = Http::timeout(10)->get($sitemapUrl);
            $exists = $response->successful();

            $result = [
                'exists' => $exists,
                'url' => $sitemapUrl,
                'status' => 'pass',
                'message' => $exists ? 'sitemap.xml found' : 'sitemap.xml not found',
            ];

            if (!$exists) {
                $result['status'] = 'warning';
                $this->warnings[] = [
                    'type' => 'seo',
                    'message' => 'sitemap.xml not found',
                    'location' => $sitemapUrl,
                    'recommendation' => 'Create a sitemap.xml to help search engines index your site.',
                ];
            } else {
                $this->passes[] = 'sitemap.xml is accessible';
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'url' => $sitemapUrl,
                'error' => $e->getMessage(),
                'status' => 'warning',
                'message' => 'Could not check sitemap.xml',
            ];
        }
    }

    /**
     * Check Schema.org markup
     */
    protected function checkSchema(SymfonyCrawler $crawler): array
    {
        $schemaTypes = [];
        $hasSchema = false;

        // Check for JSON-LD
        $jsonLdScripts = $crawler->filter('script[type="application/ld+json"]');

        if ($jsonLdScripts->count() > 0) {
            $hasSchema = true;
            $jsonLdScripts->each(function (SymfonyCrawler $node) use (&$schemaTypes) {
                $json = json_decode($node->text(), true);
                if (isset($json['@type'])) {
                    $schemaTypes[] = $json['@type'];
                }
                if (isset($json['@graph'])) {
                    foreach ($json['@graph'] as $item) {
                        if (isset($item['@type'])) {
                            $schemaTypes[] = $item['@type'];
                        }
                    }
                }
            });
        }

        // Check for microdata
        $itemscope = $crawler->filter('[itemscope]')->count();
        if ($itemscope > 0) {
            $hasSchema = true;
            $schemaTypes[] = 'microdata';
        }

        // Check for RDFa
        $typeof = $crawler->filter('[typeof]')->count();
        if ($typeof > 0) {
            $hasSchema = true;
            $schemaTypes[] = 'rdfa';
        }

        $result = [
            'has_schema' => $hasSchema,
            'types' => array_unique($schemaTypes),
            'status' => $hasSchema ? 'pass' : 'info',
            'message' => $hasSchema ? 'Schema markup found' : 'No Schema markup detected',
        ];

        if (!$hasSchema) {
            $this->warnings[] = [
                'type' => 'seo',
                'message' => 'No Schema.org markup detected',
                'recommendation' => 'Add structured data markup to enhance search result appearance.',
            ];
        } else {
            $this->passes[] = 'Schema.org markup is present';
        }

        return $result;
    }

    /**
     * Check page speed (basic check)
     */
    protected function checkSpeed(string $url, string $html): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)->withoutVerifying()->get($url);
            $loadTime = microtime(true) - $startTime;

            $size = strlen($html);
            $sizeKB = round($size / 1024, 2);

            $result = [
                'load_time' => round($loadTime, 3),
                'size_kb' => $sizeKB,
                'status' => 'pass',
                'message' => '',
            ];

            if ($loadTime > 3) {
                $result['status'] = 'fail';
                $result['message'] = "Slow page load time ({$loadTime}s)";
                $this->issues[] = [
                    'type' => 'performance',
                    'severity' => 'high',
                    'message' => "Page load time is {$loadTime}s. Recommended: < 2s",
                    'recommendation' => 'Optimize images, minify CSS/JS, and consider CDN usage.',
                ];
            } elseif ($loadTime > 2) {
                $result['status'] = 'warning';
                $result['message'] = "Page load time could be better ({$loadTime}s)";
                $this->warnings[] = [
                    'type' => 'performance',
                    'message' => "Page load time is {$loadTime}s. Recommended: < 2s",
                    'recommendation' => 'Consider optimizing page resources.',
                ];
            } else {
                $this->passes[] = 'Good page load time';
            }

            if ($sizeKB > 2000) {
                $this->warnings[] = [
                    'type' => 'performance',
                    'message' => "Page size is large ({$sizeKB}KB). Consider optimization.",
                    'recommendation' => 'Reduce page size by optimizing images and minifying code.',
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error',
                'message' => 'Could not check page speed',
            ];
        }
    }

    /**
     * Check mobile responsiveness
     */
    protected function checkMobile(SymfonyCrawler $crawler): array
    {
        $viewport = $crawler->filterXPath('//meta[@name="viewport"]')->first();
        $hasViewport = $viewport->count() > 0;

        $result = [
            'has_viewport' => $hasViewport,
            'viewport_content' => $hasViewport ? $viewport->attr('content') : null,
            'status' => $hasViewport ? 'pass' : 'fail',
            'message' => $hasViewport ? 'Mobile viewport configured' : 'Missing viewport meta tag',
        ];

        if (!$hasViewport) {
            $this->issues[] = [
                'type' => 'mobile',
                'severity' => 'critical',
                'message' => 'Missing viewport meta tag for mobile',
                'location' => '<head>',
                'recommendation' => 'Add: <meta name="viewport" content="width=device-width, initial-scale=1">',
            ];
        } else {
            $this->passes[] = 'Mobile viewport is configured';
        }

        return $result;
    }

    /**
     * Check SSL/HTTPS
     */
    protected function checkSSL(string $url): array
    {
        $hasSSL = str_starts_with($url, 'https://');

        $result = [
            'has_ssl' => $hasSSL,
            'protocol' => $hasSSL ? 'https' : 'http',
            'status' => $hasSSL ? 'pass' : 'fail',
            'message' => $hasSSL ? 'HTTPS enabled' : 'Not using HTTPS',
        ];

        if (!$hasSSL) {
            $this->issues[] = [
                'type' => 'security',
                'severity' => 'critical',
                'message' => 'Website is not using HTTPS',
                'recommendation' => 'Install an SSL certificate and redirect all HTTP traffic to HTTPS.',
            ];
        } else {
            $this->passes[] = 'HTTPS is enabled';
        }

        return $result;
    }

    /**
     * Check meta viewport
     */
    protected function checkMetaViewport(SymfonyCrawler $crawler): array
    {
        $viewport = $crawler->filterXPath('//meta[@name="viewport"]')->first();
        $hasViewport = $viewport->count() > 0;
        $content = $hasViewport ? $viewport->attr('content') : '';

        $result = [
            'exists' => $hasViewport,
            'content' => $content,
            'has_width' => $hasViewport && str_contains($content, 'width='),
            'has_initial_scale' => $hasViewport && str_contains($content, 'initial-scale='),
            'status' => 'pass',
            'message' => '',
        ];

        if (!$hasViewport) {
            $result['status'] = 'fail';
            $result['message'] = 'Missing viewport meta tag';
        } else {
            $this->passes[] = 'Viewport meta tag is present';
        }

        return $result;
    }

    /**
     * Check Open Graph tags
     */
    protected function checkOpenGraph(SymfonyCrawler $crawler): array
    {
        $ogTags = ['og:title', 'og:description', 'og:image', 'og:url', 'og:type'];
        $foundTags = [];

        foreach ($ogTags as $tag) {
            $node = $crawler->filterXPath("//meta[@property='{$tag}']")->first();
            if ($node->count() > 0) {
                $foundTags[$tag] = $node->attr('content');
            }
        }

        $result = [
            'found_tags' => array_keys($foundTags),
            'total_tags' => count($foundTags),
            'coverage' => round((count($foundTags) / count($ogTags)) * 100, 2),
            'status' => count($foundTags) >= 3 ? 'pass' : 'warning',
            'message' => count($foundTags) . ' of ' . count($ogTags) . ' Open Graph tags found',
        ];

        if (count($foundTags) < 3) {
            $this->warnings[] = [
                'type' => 'social',
                'message' => 'Missing some Open Graph tags',
                'recommendation' => 'Add complete Open Graph tags for better social media sharing.',
            ];
        }

        return $result;
    }

    /**
     * Check Twitter Card tags
     */
    protected function checkTwitterCards(SymfonyCrawler $crawler): array
    {
        $twitterTags = ['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'];
        $foundTags = [];

        foreach ($twitterTags as $tag) {
            $node = $crawler->filterXPath("//meta[@name='{$tag}']")->first();
            if ($node->count() > 0) {
                $foundTags[$tag] = $node->attr('content');
            }
        }

        $result = [
            'has_twitter_card' => count($foundTags) > 0,
            'found_tags' => array_keys($foundTags),
            'total_tags' => count($foundTags),
            'status' => count($foundTags) >= 2 ? 'pass' : 'info',
            'message' => count($foundTags) . ' Twitter Card tags found',
        ];

        return $result;
    }

    /**
     * Check favicon
     */
    protected function checkFavicon(SymfonyCrawler $crawler): array
    {
        $favicon = $crawler->filterXPath('//link[@rel="icon"] | //link[@rel="shortcut icon"]')->first();
        $hasFavicon = $favicon->count() > 0;

        $result = [
            'exists' => $hasFavicon,
            'href' => $hasFavicon ? $favicon->attr('href') : null,
            'status' => $hasFavicon ? 'pass' : 'warning',
            'message' => $hasFavicon ? 'Favicon found' : 'No favicon detected',
        ];

        if (!$hasFavicon) {
            $this->warnings[] = [
                'type' => 'ui',
                'message' => 'No favicon detected',
                'recommendation' => 'Add a favicon for better brand recognition in browser tabs.',
            ];
        }

        return $result;
    }

    /**
     * Check language declaration
     */
    protected function checkLanguage(SymfonyCrawler $crawler): array
    {
        $htmlNode = $crawler->filter('html')->first();
        $hasLang = $htmlNode->count() > 0 && $htmlNode->attr('lang') !== null;
        $lang = $hasLang ? $htmlNode->attr('lang') : null;

        $result = [
            'has_language' => $hasLang,
            'language' => $lang,
            'status' => $hasLang ? 'pass' : 'warning',
            'message' => $hasLang ? "Language declared: {$lang}" : 'No language declaration',
        ];

        if (!$hasLang) {
            $this->warnings[] = [
                'type' => 'seo',
                'message' => 'Missing lang attribute on HTML tag',
                'recommendation' => 'Add lang attribute to help search engines understand page language.',
            ];
        }

        return $result;
    }

    /**
     * Check internal links
     */
    protected function checkInternalLinks(SymfonyCrawler $crawler): array
    {
        $internalLinks = 0;

        $crawler->filter('a[href]')->each(function (SymfonyCrawler $node) use (&$internalLinks) {
            $href = $node->attr('href');
            if ($href && !str_starts_with($href, 'http') && !str_starts_with($href, '//')) {
                $internalLinks++;
            }
        });

        $result = [
            'count' => $internalLinks,
            'status' => $internalLinks > 0 ? 'pass' : 'warning',
            'message' => "{$internalLinks} internal links found",
        ];

        return $result;
    }

    /**
     * Check external links
     */
    protected function checkExternalLinks(SymfonyCrawler $crawler): array
    {
        $externalLinks = [];

        $crawler->filter('a[href]')->each(function (SymfonyCrawler $node) use (&$externalLinks) {
            $href = $node->attr('href');
            if ($href && (str_starts_with($href, 'http://') || str_starts_with($href, 'https://'))) {
                $externalLinks[] = $href;
            }
        });

        $result = [
            'count' => count($externalLinks),
            'links' => array_slice($externalLinks, 0, 10), // First 10
            'status' => 'pass',
            'message' => count($externalLinks) . ' external links found',
        ];

        return $result;
    }

    /**
     * Calculate overall SEO score
     */
    protected function calculateSeoScore(array $checks): int
    {
        $score = 0;
        $maxScore = 100;

        foreach ($this->weights as $check => $weight) {
            if (isset($checks[$check])) {
                $status = $checks[$check]['status'] ?? 'unknown';

                switch ($status) {
                    case 'pass':
                        $score += $weight;
                        break;
                    case 'warning':
                        $score += $weight * 0.5;
                        break;
                    case 'info':
                        $score += $weight * 0.75;
                        break;
                    case 'fail':
                        // No points
                        break;
                }
            }
        }

        return min(100, max(0, round($score)));
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(array $checks): array
    {
        $recommendations = [];

        foreach ($checks as $check => $result) {
            if (in_array($result['status'], ['fail', 'warning'])) {
                $recommendations[] = [
                    'check' => $check,
                    'message' => $result['message'],
                    'priority' => $result['status'] === 'fail' ? 'high' : 'medium',
                ];
            }
        }

        return $recommendations;
    }
}
