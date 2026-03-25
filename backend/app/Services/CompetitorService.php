<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompetitorService
{
    protected array $config;
    protected ?string $googleApiKey;
    protected ?string $googleSearchEngineId;
    protected TechStackService $techStackService;

    public function __construct(TechStackService $techStackService)
    {
        $this->config = config('auditor.competitors') ?? [];
        $this->googleApiKey = env('GOOGLE_SEARCH_API_KEY');
        $this->googleSearchEngineId = env('GOOGLE_SEARCH_ENGINE_ID');
        $this->techStackService = $techStackService;
    }

    /**
     * Find competitors for a given URL
     */
    public function findCompetitors(string $url, ?string $industry = null): array
    {
        $competitors = [];

        // Extract domain and keywords from URL
        $domain = parse_url($url, PHP_URL_HOST);
        $keywords = $this->extractKeywords($url, $industry);

        // Method 1: Google Custom Search API
        if ($this->googleApiKey && $this->googleSearchEngineId) {
            $googleResults = $this->searchGoogle($keywords);
            $competitors = array_merge($competitors, $googleResults);
        }

        // Method 2: Similar sites based on industry
        if ($industry) {
            $industryCompetitors = $this->findIndustryCompetitors($industry, $domain);
            $competitors = array_merge($competitors, $industryCompetitors);
        }

        // Method 3: Analyze similar tech stack sites from GitHub
        $githubCompetitors = $this->findSimilarTechStackCompetitors($url);
        $competitors = array_merge($competitors, $githubCompetitors);

        // Remove duplicates and the original site
        $competitors = $this->uniqueCompetitors($competitors, $domain);

        // Analyze each competitor
        $analyzedCompetitors = [];
        foreach (array_slice($competitors, 0, $this->config['max_results'] ?? 10) as $competitor) {
            $analyzedCompetitors[] = $this->analyzeCompetitor($competitor['url'], $competitor);
        }

        return [
            'original_url' => $url,
            'domain' => $domain,
            'industry' => $industry,
            'competitors' => $analyzedCompetitors,
            'total_found' => count($competitors),
        ];
    }

    /**
     * Analyze a specific competitor
     */
    public function analyzeCompetitor(string $competitorUrl, array $metadata = []): array
    {
        try {
            $startTime = microtime(true);

            // Fetch the page
            $response = Http::timeout(15)->withoutVerifying()->get($competitorUrl);

            if (!$response->successful()) {
                return [
                    'url' => $competitorUrl,
                    'error' => 'Failed to fetch page',
                    'status_code' => $response->status(),
                    ...$metadata,
                ];
            }

            $html = $response->body();
            $headers = $response->headers();

            // Analyze tech stack
            $techStack = $this->techStackService->detectTechStack($html, $competitorUrl, $headers);

            // Extract basic info
            $info = $this->extractBasicInfo($html);

            // Calculate similarity score (basic heuristic)
            $similarityScore = $this->calculateSimilarityScore($metadata, $techStack);

            $loadTime = microtime(true) - $startTime;

            return [
                'url' => $competitorUrl,
                'name' => $info['name'] ?? $this->extractDomainName($competitorUrl),
                'title' => $info['title'] ?? '',
                'description' => $info['description'] ?? '',
                'similarity_score' => $similarityScore,
                'tech_stack' => $techStack,
                'features' => $this->detectFeatures($html, $techStack),
                'traffic_estimate' => $this->estimateTraffic($competitorUrl),
                'page_size' => strlen($html),
                'load_time' => round($loadTime, 2),
                'has_analytics' => !empty($techStack['analytics']),
                'has_crm' => $this->hasCRM($html),
                'social_links' => $this->extractSocialLinks($html),
                'metadata' => $metadata,
            ];
        } catch (\Exception $e) {
            return [
                'url' => $competitorUrl,
                'error' => $e->getMessage(),
                ...$metadata,
            ];
        }
    }

    /**
     * Compare features between our site and competitors
     */
    public function compareFeatures(array $ourFeatures, array $competitors): array
    {
        $comparison = [
            'our_features' => $ourFeatures,
            'competitor_features' => [],
            'common_features' => [],
            'unique_features' => [],
            'missing_features' => [],
        ];

        $allCompetitorFeatures = [];
        foreach ($competitors as $competitor) {
            if (isset($competitor['features'])) {
                foreach ($competitor['features'] as $feature) {
                    $allCompetitorFeatures[] = $feature;
                }
                $comparison['competitor_features'][$competitor['url']] = $competitor['features'];
            }
        }

        $allCompetitorFeatures = array_unique($allCompetitorFeatures);

        // Find common features
        $comparison['common_features'] = array_intersect($ourFeatures, $allCompetitorFeatures);

        // Find our unique features
        $comparison['unique_features'] = array_diff($ourFeatures, $allCompetitorFeatures);

        // Find features we're missing
        $comparison['missing_features'] = array_diff($allCompetitorFeatures, $ourFeatures);

        return $comparison;
    }

    /**
     * Generate competitor report
     */
    public function generateCompetitorReport(array $competitors): array
    {
        $report = [
            'summary' => [
                'total_competitors' => count($competitors),
                'avg_similarity' => 0,
                'most_similar' => null,
                'top_performer' => null,
            ],
            'tech_stack_analysis' => [
                'common_frameworks' => [],
                'common_cms' => [],
                'common_analytics' => [],
            ],
            'feature_gaps' => [],
            'opportunities' => [],
            'recommendations' => [],
        ];

        if (empty($competitors)) {
            return $report;
        }

        // Calculate average similarity
        $totalSimilarity = 0;
        $mostSimilar = null;
        $highestSimilarity = 0;

        $frameworks = [];
        $cms = [];
        $analytics = [];

        foreach ($competitors as $competitor) {
            if (!isset($competitor['error'])) {
                $similarity = $competitor['similarity_score'] ?? 0;
                $totalSimilarity += $similarity;

                if ($similarity > $highestSimilarity) {
                    $highestSimilarity = $similarity;
                    $mostSimilar = $competitor;
                }

                // Collect tech stack data
                if (isset($competitor['tech_stack'])) {
                    foreach ($competitor['tech_stack']['frameworks'] ?? [] as $fw) {
                        $frameworks[] = $fw['name'];
                    }
                    foreach ($competitor['tech_stack']['cms'] ?? [] as $c) {
                        $cms[] = $c['name'];
                    }
                    foreach ($competitor['tech_stack']['analytics'] ?? [] as $a) {
                        $analytics[] = $a['name'];
                    }
                }
            }
        }

        $report['summary']['avg_similarity'] = count($competitors) > 0
            ? round($totalSimilarity / count($competitors), 2)
            : 0;
        $report['summary']['most_similar'] = $mostSimilar;

        // Find common tech
        $report['tech_stack_analysis']['common_frameworks'] = $this->getMostCommon($frameworks, 3);
        $report['tech_stack_analysis']['common_cms'] = $this->getMostCommon($cms, 3);
        $report['tech_stack_analysis']['common_analytics'] = $this->getMostCommon($analytics, 3);

        // Generate recommendations
        $report['recommendations'] = $this->generateCompetitorRecommendations($competitors, $report);

        return $report;
    }

    /**
     * Search Google for similar sites
     */
    protected function searchGoogle(array $keywords): array
    {
        $results = [];

        try {
            $query = implode(' ', array_slice($keywords, 0, 5));

            $response = Http::withoutVerifying()->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $this->googleApiKey,
                'cx' => $this->googleSearchEngineId,
                'q' => $query,
                'num' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                foreach ($data['items'] ?? [] as $item) {
                    $results[] = [
                        'url' => $item['link'],
                        'title' => $item['title'],
                        'description' => $item['snippet'] ?? '',
                        'source' => 'google_search',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Google search error: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Find competitors in the same industry
     */
    protected function findIndustryCompetitors(string $industry, string $excludeDomain): array
    {
        // Known competitor lists by industry
        $industryCompetitors = [
            'e-commerce' => [
                ['url' => 'https://www.shopify.com', 'name' => 'Shopify'],
                ['url' => 'https://www.bigcommerce.com', 'name' => 'BigCommerce'],
                ['url' => 'https://www.woocommerce.com', 'name' => 'WooCommerce'],
            ],
            'saas' => [
                ['url' => 'https://www.salesforce.com', 'name' => 'Salesforce'],
                ['url' => 'https://www.hubspot.com', 'name' => 'HubSpot'],
                ['url' => 'https://www.zendesk.com', 'name' => 'Zendesk'],
            ],
            'fintech' => [
                ['url' => 'https://www.stripe.com', 'name' => 'Stripe'],
                ['url' => 'https://www.paypal.com', 'name' => 'PayPal'],
                ['url' => 'https://www.squareup.com', 'name' => 'Square'],
            ],
            'healthcare' => [
                ['url' => 'https://www.teladoc.com', 'name' => 'Teladoc'],
                ['url' => 'https://www.healthgrades.com', 'name' => 'Healthgrades'],
            ],
            'education' => [
                ['url' => 'https://www.udemy.com', 'name' => 'Udemy'],
                ['url' => 'https://www.coursera.org', 'name' => 'Coursera'],
                ['url' => 'https://www.khanacademy.org', 'name' => 'Khan Academy'],
            ],
            'news' => [
                ['url' => 'https://www.cnn.com', 'name' => 'CNN'],
                ['url' => 'https://www.bbc.com', 'name' => 'BBC'],
                ['url' => 'https://www.nytimes.com', 'name' => 'NY Times'],
            ],
            'travel' => [
                ['url' => 'https://www.booking.com', 'name' => 'Booking.com'],
                ['url' => 'https://www.expedia.com', 'name' => 'Expedia'],
                ['url' => 'https://www.airbnb.com', 'name' => 'Airbnb'],
            ],
        ];

        $industryLower = strtolower($industry);
        $competitors = $industryCompetitors[$industryLower] ?? [];

        // Filter out the original domain
        return array_filter($competitors, function ($c) use ($excludeDomain) {
            return !str_contains(parse_url($c['url'], PHP_URL_HOST), $excludeDomain);
        });
    }

    /**
     * Find competitors with similar tech stack from GitHub
     */
    protected function findSimilarTechStackCompetitors(string $url): array
    {
        $competitors = [];

        try {
            // First, get the tech stack
            $response = Http::timeout(15)->withoutVerifying()->get($url);
            if (!$response->successful()) {
                return $competitors;
            }

            $techStack = $this->techStackService->detectTechStack(
                $response->body(),
                $url,
                $response->headers()
            );

            // Find live sites using similar tech (based on common patterns)
            foreach ($techStack['frameworks'] ?? [] as $framework) {
                $competitors = array_merge($competitors, $this->findExampleSites($framework['name']));
            }
        } catch (\Exception $e) {
            Log::error("Tech stack competitor search error: {$e->getMessage()}");
        }

        return $competitors;
    }

    /**
     * Find example sites using a particular technology
     */
    protected function findExampleSites(string $technology): array
    {
        // Known example sites by technology
        $exampleSites = [
            'React' => [
                ['url' => 'https://www.facebook.com', 'name' => 'Facebook'],
                ['url' => 'https://www.instagram.com', 'name' => 'Instagram'],
                ['url' => 'https://www.airbnb.com', 'name' => 'Airbnb'],
            ],
            'Vue.js' => [
                ['url' => 'https://www.adobe.com', 'name' => 'Adobe'],
                ['url' => 'https://www.grammarly.com', 'name' => 'Grammarly'],
                ['url' => 'https://www.behance.net', 'name' => 'Behance'],
            ],
            'Angular' => [
                ['url' => 'https://www.upwork.com', 'name' => 'Upwork'],
                ['url' => 'https://www.weather.com', 'name' => 'Weather.com'],
                ['url' => 'https://www.forbes.com', 'name' => 'Forbes'],
            ],
            'Next.js' => [
                ['url' => 'https://www.tiktok.com', 'name' => 'TikTok'],
                ['url' => 'https://www.twitch.tv', 'name' => 'Twitch'],
            ],
            'Laravel' => [
                ['url' => 'https://www.laravel.com', 'name' => 'Laravel'],
                ['url' => 'https://www.startups.com', 'name' => 'Startups.com'],
            ],
            'WordPress' => [
                ['url' => 'https://www.techcrunch.com', 'name' => 'TechCrunch'],
                ['url' => 'https://www.wired.com', 'name' => 'Wired'],
            ],
        ];

        return $exampleSites[$technology] ?? [];
    }

    /**
     * Extract keywords from URL
     */
    protected function extractKeywords(string $url, ?string $industry = null): array
    {
        $keywords = [];

        // Extract from domain
        $domain = parse_url($url, PHP_URL_HOST);
        $domainParts = explode('.', $domain);
        $keywords[] = $domainParts[0] ?? '';

        // Extract from path
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $pathParts = explode('/', trim($path, '/'));
            $keywords = array_merge($keywords, $pathParts);
        }

        // Add industry keywords
        if ($industry) {
            $industryKeywords = [
                'e-commerce' => ['shop', 'store', 'buy', 'online shopping'],
                'saas' => ['software', 'platform', 'app', 'tool'],
                'fintech' => ['finance', 'banking', 'payment', 'financial'],
                'healthcare' => ['health', 'medical', 'doctor', 'clinic'],
                'education' => ['learn', 'course', 'training', 'education'],
            ];

            $keywords = array_merge($keywords, $industryKeywords[strtolower($industry)] ?? []);
        }

        return array_filter($keywords);
    }

    /**
     * Calculate similarity score between sites
     */
    protected function calculateSimilarityScore(array $metadata, array $techStack): float
    {
        $score = 0.5; // Base score

        // Adjust based on tech stack similarity
        if (!empty($techStack['frameworks'])) {
            $score += 0.1;
        }

        if (!empty($techStack['cms'])) {
            $score += 0.1;
        }

        if (!empty($techStack['analytics'])) {
            $score += 0.05;
        }

        // Adjust based on metadata
        if (isset($metadata['relevance'])) {
            $score *= $metadata['relevance'];
        }

        return min(1.0, round($score, 2));
    }

    /**
     * Extract basic info from HTML
     */
    protected function extractBasicInfo(string $html): array
    {
        $info = [
            'title' => '',
            'description' => '',
            'name' => '',
        ];

        // Extract title
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
            $info['title'] = trim($matches[1]);
        }

        // Extract meta description
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $matches)) {
            $info['description'] = trim($matches[1]);
        }

        // Extract og:site_name
        if (preg_match('/<meta\s+property=["\']og:site_name["\']\s+content=["\'](.*?)["\']/is', $html, $matches)) {
            $info['name'] = trim($matches[1]);
        }

        return $info;
    }

    /**
     * Extract domain name from URL
     */
    protected function extractDomainName(string $url): string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        return str_replace('www.', '', $domain ?? $url);
    }

    /**
     * Detect features from HTML
     */
    protected function detectFeatures(string $html, array $techStack): array
    {
        $features = [];

        // Check for common features
        $featurePatterns = [
            'Blog' => ['/blog', '/news', '/posts'],
            'E-commerce' => ['cart', 'checkout', 'shopify', 'woocommerce'],
            'User Authentication' => ['login', 'register', 'signup', 'sign-in'],
            'Search' => ['search', 'type="search"'],
            'Social Media Integration' => ['facebook.com', 'twitter.com', 'instagram.com'],
            'Live Chat' => ['intercom', 'drift', 'zendesk', 'tawk.to'],
            'Newsletter' => ['newsletter', 'subscribe', 'mailchimp'],
            'Pricing Page' => ['/pricing', '/plans'],
            'Contact Form' => ['contact', 'form'],
            'FAQ Section' => ['faq', 'frequently asked'],
            'Testimonials' => ['testimonial', 'review', 'rating'],
            'Dark Mode' => ['dark-mode', 'darkmode', 'theme-toggle'],
            'Multi-language' => ['lang=', 'locale=', 'language'],
        ];

        $htmlLower = strtolower($html);

        foreach ($featurePatterns as $feature => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($htmlLower, strtolower($pattern))) {
                    $features[] = $feature;
                    break;
                }
            }
        }

        // Check tech stack specific features
        if (!empty($techStack['analytics'])) {
            $features[] = 'Analytics';
        }

        if (!empty($techStack['payments'])) {
            $features[] = 'Payment Processing';
        }

        return array_unique($features);
    }

    /**
     * Estimate traffic (basic heuristic)
     */
    protected function estimateTraffic(string $url): array
    {
        // This is a simplified estimation
        // In production, you'd use APIs like SimilarWeb or Alexa

        $domain = parse_url($url, PHP_URL_HOST);
        $domainLength = strlen($domain);

        // Very rough heuristic based on domain characteristics
        $estimate = 'low';

        if (in_array($domain, ['www.facebook.com', 'www.google.com', 'www.youtube.com'])) {
            $estimate = 'very_high';
        } elseif (str_contains($domain, '.com') && $domainLength < 15) {
            $estimate = 'medium';
        }

        return [
            'level' => $estimate,
            'note' => 'Estimation only - use SimilarWeb API for accurate data',
        ];
    }

    /**
     * Check if site has CRM
     */
    protected function hasCRM(string $html): bool
    {
        $crmPatterns = ['hubspot', 'salesforce', 'pardot', 'marketo', 'mailchimp'];

        foreach ($crmPatterns as $pattern) {
            if (str_contains(strtolower($html), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract social media links
     */
    protected function extractSocialLinks(string $html): array
    {
        $socials = [];

        $patterns = [
            'facebook' => '/facebook\.com\/[\w.-]+/i',
            'twitter' => '/twitter\.com\/[\w.-]+/i',
            'instagram' => '/instagram\.com\/[\w.-]+/i',
            'linkedin' => '/linkedin\.com\/[\w.-]+/i',
            'youtube' => '/youtube\.com\/[\w.-]+/i',
            'tiktok' => '/tiktok\.com\/@[\w.-]+/i',
        ];

        foreach ($patterns as $platform => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $socials[$platform] = $matches[0];
            }
        }

        return $socials;
    }

    /**
     * Remove duplicate competitors and filter out original site
     */
    protected function uniqueCompetitors(array $competitors, string $excludeDomain): array
    {
        $seen = [];
        $unique = [];

        foreach ($competitors as $competitor) {
            $url = $competitor['url'] ?? '';
            $domain = parse_url($url, PHP_URL_HOST);

            // Skip if it's the original domain or already seen
            if (str_contains($domain, $excludeDomain) || in_array($domain, $seen)) {
                continue;
            }

            $seen[] = $domain;
            $unique[] = $competitor;
        }

        return $unique;
    }

    /**
     * Get most common items from array
     */
    protected function getMostCommon(array $items, int $count): array
    {
        $counts = array_count_values($items);
        arsort($counts);

        return array_slice(array_keys($counts), 0, $count, true);
    }

    /**
     * Generate competitor recommendations
     */
    protected function generateCompetitorRecommendations(array $competitors, array $report): array
    {
        $recommendations = [];

        // Analyze tech stack trends
        if (!empty($report['tech_stack_analysis']['common_frameworks'])) {
            $topFramework = $report['tech_stack_analysis']['common_frameworks'][0];
            $recommendations[] = [
                'type' => 'technology',
                'message' => "Top competitors are using {$topFramework}",
                'action' => "Consider evaluating {$topFramework} for your tech stack.",
            ];
        }

        // Feature gap analysis
        if (!empty($report['feature_gaps'])) {
            $recommendations[] = [
                'type' => 'feature',
                'message' => 'Competitors have features you lack',
                'action' => 'Evaluate adding missing features to stay competitive.',
            ];
        }

        return $recommendations;
    }
}
