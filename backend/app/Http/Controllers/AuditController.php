<?php

namespace App\Http\Controllers;

use App\Services\CrawlerService;
use App\Services\TechStackService;
use App\Services\SeoService;
use App\Services\CompetitorService;
use App\Services\GitHubService;
use App\Services\SubscriptionService;
use App\Models\Audit;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuditController extends Controller
{
    protected CrawlerService $crawler;
    protected TechStackService $techStack;
    protected SeoService $seo;
    protected CompetitorService $competitor;
    protected GitHubService $github;
    protected SubscriptionService $subscription;

    public function __construct(
        CrawlerService $crawler,
        TechStackService $techStack,
        SeoService $seo,
        CompetitorService $competitor,
        GitHubService $github,
        SubscriptionService $subscription
    ) {
        $this->crawler = $crawler;
        $this->techStack = $techStack;
        $this->seo = $seo;
        $this->competitor = $competitor;
        $this->github = $github;
        $this->subscription = $subscription;
    }

    /**
     * Run a comprehensive website audit
     */
    public function audit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'max_depth' => 'integer|min:1|max:10',
            'use_browser' => 'boolean',
            'check_competitors' => 'boolean',
            'check_github' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $url = $request->input('url');
        $options = [
            'max_depth' => $request->input('max_depth', 3),
            'use_browser' => $request->input('use_browser', true),
            'check_competitors' => $request->input('check_competitors', true),
            'check_github' => $request->input('check_github', true),
        ];

        try {
            // Create audit record
            $audit = Audit::create([
                'url' => $url,
                'status' => 'running',
                'overall_score' => null,
                'seo_score' => null,
            ]);

            // Run the audit (can be queued in production)
            $results = $this->performAudit($url, $options);

            // Update audit record
            $audit->update([
                'status' => 'completed',
                'overall_score' => $results['overall_score'] ?? null,
                'seo_score' => $results['seo']['score'] ?? null,
                'pages_count' => $results['crawler']['pages_crawled'] ?? 1,
                'load_time' => $results['crawler']['total_time'] ?? null,
                'tech_stack' => $results['tech_stack'] ?? null,
                'issues' => $results['issues'] ?? null,
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'audit_id' => $audit->id,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error("Audit failed: {$e->getMessage()}", [
                'url' => $url,
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($audit)) {
                $audit->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform the actual audit
     */
    protected function performAudit(string $url, array $options): array
    {
        $results = [];

        // 1. Crawl the website
        $crawlerResults = $this->crawler->crawlSite($url, $options);
        $results['crawler'] = $crawlerResults;

        if (!$crawlerResults['success']) {
            throw new \Exception("Crawling failed: " . ($crawlerResults['error'] ?? 'Unknown error'));
        }

        // Get the first page for detailed analysis
        $firstPage = $crawlerResults['pages'][0] ?? null;
        if (!$firstPage) {
            throw new \Exception('No pages were crawled');
        }

        $html = $firstPage['html'] ?? '';
        $headers = $firstPage['headers'] ?? [];

        // 2. Detect tech stack
        $techStack = $this->techStack->detectTechStack($html, $url, $headers);
        $results['tech_stack'] = $techStack;

        // 3. SEO analysis
        $seoResults = $this->seo->analyzeSeo($url, $html);
        $results['seo'] = $seoResults;

        // 4. Collect issues
        $allIssues = array_merge(
            $seoResults['issues'] ?? [],
            $crawlerResults['issues'] ?? []
        );
        $results['issues'] = $allIssues;

        // 5. Competitor analysis (optional)
        if ($options['check_competitors']) {
            try {
                $industry = $this->guessIndustry($url, $techStack);
                $competitorResults = $this->competitor->findCompetitors($url, $industry);
                $results['competitors'] = $competitorResults;
            } catch (\Exception $e) {
                $results['competitors'] = ['error' => $e->getMessage()];
            }
        }

        // 6. GitHub similar projects (optional)
        if ($options['check_github']) {
            try {
                $githubResults = $this->github->searchSimilarToWebsite($url, $techStack);
                $results['github'] = $githubResults;
            } catch (\Exception $e) {
                $results['github'] = ['error' => $e->getMessage()];
            }
        }

        // 7. Calculate overall score
        $results['overall_score'] = $this->calculateOverallScore($results);

        // 8. Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results);

        return $results;
    }

    /**
     * Get a specific audit
     */
    public function show(string $id): JsonResponse
    {
        $audit = Audit::with('issues', 'techStacks', 'competitors')->find($id);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $audit,
        ]);
    }

    /**
     * List all audits
     */
    public function index(Request $request): JsonResponse
    {
        $query = Audit::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('url')) {
            $query->where('url', 'like', '%' . $request->input('url') . '%');
        }

        $audits = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }

    /**
     * Delete an audit
     */
    public function destroy(string $id): JsonResponse
    {
        $audit = Audit::find($id);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
            ], 404);
        }

        $audit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Audit deleted successfully',
        ]);
    }

    /**
     * Get audit statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_audits' => Audit::count(),
            'completed_audits' => Audit::where('status', 'completed')->count(),
            'pending_audits' => Audit::where('status', 'pending')->count(),
            'failed_audits' => Audit::where('status', 'failed')->count(),
            'avg_score' => Audit::where('status', 'completed')
                ->whereNotNull('overall_score')
                ->avg('overall_score'),
            'avg_seo_score' => Audit::where('status', 'completed')
                ->whereNotNull('seo_score')
                ->avg('seo_score'),
            'most_audited_sites' => Audit::select('url')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('url')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Crawl a website
     */
    public function crawl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'max_depth' => 'integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = $this->crawler->crawlSite(
                $request->input('url'),
                [
                    'max_depth' => $request->input('max_depth', 2),
                    'max_pages' => $request->input('max_pages', 20),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze tech stack
     */
    public function analyzeTechStack(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get($request->input('url'));
            $html = $response->body();
            $headers = $response->headers();

            $techStack = $this->techStack->detectTechStack($html, $request->input('url'), $headers);

            return response()->json([
                'success' => true,
                'data' => $techStack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze SEO
     */
    public function analyzeSeo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = $this->seo->analyzeSeo($request->input('url'));

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find competitors
     */
    public function findCompetitors(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'industry' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = $this->competitor->findCompetitors(
                $request->input('url'),
                $request->input('industry')
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search GitHub repositories
     */
    public function searchGitHub(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'language' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $keywords = explode(' ', $request->input('query'));
            $results = $this->github->searchRepositories(
                $keywords,
                $request->input('language')
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trending GitHub repositories
     */
    public function trending(Request $request): JsonResponse
    {
        try {
            $results = $this->github->getTrending(
                $request->input('language'),
                $request->input('period', 'monthly')
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => [],
        ];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = 'connected';
        } catch (\Exception $e) {
            $health['services']['database'] = 'disconnected';
            $health['status'] = 'degraded';
        }

        // Check Selenium connection (if configured)
        $seleniumHost = config('auditor.selenium.host');
        if ($seleniumHost) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($seleniumHost . '/status');
                $health['services']['selenium'] = $response->successful() ? 'connected' : 'disconnected';
                if (!$response->successful()) {
                    $health['status'] = 'degraded';
                }
            } catch (\Exception $e) {
                $health['services']['selenium'] = 'disconnected';
                $health['status'] = 'degraded';
            }
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Calculate overall score
     */
    protected function calculateOverallScore(array $results): int
    {
        $score = 0;
        $weights = [
            'seo' => 0.4,
            'tech_stack' => 0.1,
            'performance' => 0.3,
            'accessibility' => 0.1,
            'security' => 0.1,
        ];

        // SEO score
        $seoScore = $results['seo']['score'] ?? 0;
        $score += $seoScore * $weights['seo'];

        // Tech stack score (based on completeness)
        $techStackScore = min(100, count($results['tech_stack']['all'] ?? []) * 10);
        $score += $techStackScore * $weights['tech_stack'];

        // Performance score
        $loadTime = $results['crawler']['total_time'] ?? 0;
        $performanceScore = $loadTime < 2 ? 100 : max(0, 100 - ($loadTime - 2) * 20);
        $score += $performanceScore * $weights['performance'];

        // Accessibility score (based on alt attributes, etc.)
        $accessibilityScore = $results['seo']['checks']['images_alt']['status'] === 'pass' ? 100 : 70;
        $score += $accessibilityScore * $weights['accessibility'];

        // Security score (SSL)
        $securityScore = $results['seo']['checks']['ssl']['has_ssl'] ? 100 : 0;
        $score += $securityScore * $weights['security'];

        return round($score);
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(array $results): array
    {
        $recommendations = [];

        // SEO recommendations
        if (isset($results['seo']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['seo']['recommendations']);
        }

        // Performance recommendations
        $loadTime = $results['crawler']['total_time'] ?? 0;
        if ($loadTime > 2) {
            $recommendations[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Improve page load time',
                'description' => "Current load time is {$loadTime}s. Aim for under 2 seconds.",
                'actions' => [
                    'Optimize images',
                    'Minify CSS and JavaScript',
                    'Use a CDN',
                    'Enable compression',
                ],
            ];
        }

        // Tech stack recommendations
        if (empty($results['tech_stack']['analytics'])) {
            $recommendations[] = [
                'category' => 'analytics',
                'priority' => 'medium',
                'title' => 'Add analytics tracking',
                'description' => 'No analytics tools detected on the site.',
                'actions' => [
                    'Install Google Analytics or similar',
                    'Set up conversion tracking',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Guess website industry based on URL and tech stack
     */
    protected function guessIndustry(string $url, array $techStack): ?string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $domainLower = strtolower($domain);

        // Check for e-commerce indicators
        $ecommerceIndicators = ['shop', 'store', 'cart', 'buy', 'ecommerce'];
        if (preg_match('/(' . implode('|', $ecommerceIndicators) . ')/i', $domainLower)) {
            return 'e-commerce';
        }

        // Check for SaaS indicators
        $saasIndicators = ['app', 'software', 'platform', 'tool'];
        if (preg_match('/(' . implode('|', $saasIndicators) . ')/i', $domainLower)) {
            return 'saas';
        }

        // Check based on tech stack
        if (in_array('Shopify', array_column($techStack['cms'] ?? [], 'name'))) {
            return 'e-commerce';
        }

        if (in_array('WordPress', array_column($techStack['cms'] ?? [], 'name'))) {
            return 'content';
        }

        return null;
    }
}
