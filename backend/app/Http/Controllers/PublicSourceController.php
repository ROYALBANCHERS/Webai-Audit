<?php

namespace App\Http\Controllers;

use App\Models\PublicCrawlSource;
use App\Models\GovJob;
use App\Models\ScrapingLog;
use App\Services\TinyFishService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PublicSourceController extends Controller
{
    protected TinyFishService $tinyFishService;

    public function __construct(TinyFishService $tinyFishService)
    {
        $this->tinyFishService = $tinyFishService;
    }

    /**
     * List all public sources
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublicCrawlSource::query();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter active only
        if ($request->has('active') && $request->active) {
            $query->active();
        }

        // Featured sources first
        $sources = $query->orderBy('is_featured', 'desc')
            ->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sources->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'url' => $source->url,
                    'category' => $source->category,
                    'scraping_strategy' => $source->scraping_strategy,
                    'is_active' => $source->is_active,
                    'status' => $source->status,
                    'auto_update' => $source->auto_update,
                    'total_jobs_found' => $source->total_jobs_found,
                    'success_count' => $source->success_count,
                    'failure_count' => $source->failure_count,
                    'success_rate' => $source->success_rate,
                    'last_crawled_at' => $source->last_crawled_at?->toIso8601String(),
                    'next_crawl_at' => $source->next_crawl_at?->toIso8601String(),
                    'last_error' => $source->last_error,
                    'is_featured' => $source->is_featured,
                    'display_order' => $source->display_order,
                    'gov_jobs_count' => $source->govJobs()->count(),
                ];
            })
        ]);
    }

    /**
     * Get a specific source
     */
    public function show(int $id): JsonResponse
    {
        $source = PublicCrawlSource::with('govJobs', 'scrapingLogs')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $source
        ]);
    }

    /**
     * Create new source (requires admin secret)
     */
    public function store(Request $request): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'category' => 'required|in:ssc,upsc,ibps,railway,defence,teaching,state,general',
            'agentql_query' => 'nullable|string',
            'scraping_strategy' => 'required|in:auto,agentql,guzzle,rss',
            'selectors' => 'nullable|array',
            'auto_update' => 'boolean',
            'crawl_frequency' => 'integer|min:300|max:86400',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $source = PublicCrawlSource::create([
            'name' => $request->name,
            'url' => $request->url,
            'category' => $request->category,
            'agentql_query' => $request->agentql_query,
            'scraping_strategy' => $request->scraping_strategy,
            'selectors' => $request->selectors,
            'auto_update' => $request->auto_update ?? true,
            'crawl_frequency' => $request->crawl_frequency ?? 3600,
            'is_featured' => $request->is_featured ?? false,
            'display_order' => $request->display_order ?? 0,
            'next_crawl_at' => now(),
            'status' => 'pending'
        ]);

        Log::info("New source created: {$source->name} by admin");

        return response()->json([
            'success' => true,
            'message' => 'Source added successfully',
            'data' => $source
        ], 201);
    }

    /**
     * Test crawl source
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $source = PublicCrawlSource::findOrFail($id);

        Log::info("Testing source: {$source->name}");

        $result = $this->tinyFishService->scrapeWithFallback($source);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get jobs from specific source
     */
    public function jobs(Request $request, int $id): JsonResponse
    {
        $source = PublicCrawlSource::findOrFail($id);

        $jobs = GovJob::where('public_source_id', $source->id)
            ->where('source_type', 'public')
            ->latest('crawled_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
                'url' => $source->url,
                'category' => $source->category
            ]
        ]);
    }

    /**
     * Get scraping logs for source (requires admin secret)
     */
    public function logs(Request $request, int $id): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $source = PublicCrawlSource::findOrFail($id);

        $logs = ScrapingLog::where('source_id', $source->id)
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Update source (requires admin secret)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $source = PublicCrawlSource::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:2048',
            'category' => 'sometimes|in:ssc,upsc,ibps,railway,defence,teaching,state,general',
            'agentql_query' => 'nullable|string',
            'scraping_strategy' => 'sometimes|in:auto,agentql,guzzle,rss',
            'selectors' => 'sometimes|array',
            'auto_update' => 'sometimes|boolean',
            'crawl_frequency' => 'sometimes|integer|min:300|max:86400',
            'is_featured' => 'sometimes|boolean',
            'display_order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $source->update($request->only([
            'name', 'url', 'category', 'agentql_query', 'scraping_strategy',
            'selectors', 'auto_update', 'crawl_frequency', 'is_featured', 'display_order'
        ]));

        Log::info("Source updated: {$source->name} by admin");

        return response()->json([
            'success' => true,
            'message' => 'Source updated successfully',
            'data' => $source
        ]);
    }

    /**
     * Toggle source active status (requires admin secret)
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $source = PublicCrawlSource::findOrFail($id);

        if ($source->is_active) {
            $source->pause();
            $message = 'Source paused';
        } else {
            $source->resume();
            $message = 'Source activated';
        }

        $source->save();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $source->fresh()
        ]);
    }

    /**
     * Delete source (requires admin secret)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $source = PublicCrawlSource::findOrFail($id);
        $name = $source->name;

        $source->delete();

        Log::info("Source deleted: {$name} by admin");

        return response()->json([
            'success' => true,
            'message' => 'Source deleted successfully'
        ]);
    }

    /**
     * Trigger manual crawl for all active sources (requires admin secret)
     */
    public function crawlAll(Request $request): JsonResponse
    {
        // Verify admin secret
        $adminSecret = $request->header('X-Admin-Secret');
        if ($adminSecret !== env('ADMIN_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin secret required.'
            ], 403);
        }

        $sources = PublicCrawlSource::active()->get();
        $results = [
            'success' => true,
            'total' => $sources->count(),
            'crawled' => 0,
            'failed' => 0,
            'total_jobs' => 0,
            'sources' => []
        ];

        foreach ($sources as $source) {
            try {
                $result = $this->tinyFishService->scrapeWithFallback($source);

                if ($result['success']) {
                    $jobsAdded = $this->tinyFishService->saveJobs($result['jobs'], $source);
                    $results['crawled']++;
                    $results['total_jobs'] += $jobsAdded;

                    $results['sources'][] = [
                        'id' => $source->id,
                        'name' => $source->name,
                        'status' => 'success',
                        'jobs_found' => count($result['jobs']),
                        'jobs_added' => $jobsAdded,
                        'method' => $result['method']
                    ];
                } else {
                    $results['failed']++;
                    $results['sources'][] = [
                        'id' => $source->id,
                        'name' => $source->name,
                        'status' => 'failed',
                        'error' => $result['error'] ?? 'Unknown error'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['sources'][] = [
                    'id' => $source->id,
                    'name' => $source->name,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info("Manual crawl completed: {$results['crawled']}/{$results['total']} successful");

        return response()->json($results);
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        $totalSources = PublicCrawlSource::count();
        $activeSources = PublicCrawlSource::active()->count();
        $featuredSources = PublicCrawlSource::featured()->count();
        $totalJobs = GovJob::where('source_type', 'public')->count();
        $totalLogs = ScrapingLog::count();

        $recentLogs = ScrapingLog::latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sources' => $totalSources,
                'active_sources' => $activeSources,
                'featured_sources' => $featuredSources,
                'total_jobs' => $totalJobs,
                'total_logs' => $totalLogs,
                'recent_logs' => $recentLogs
            ]
        ]);
    }
}
