<?php

namespace App\Http\Controllers;

use App\Services\WebsiteMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebsiteMonitorController extends Controller
{
    protected WebsiteMonitorService $monitorService;

    public function __construct(WebsiteMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    /**
     * Get all monitored websites
     */
    public function index()
    {
        try {
            $websites = $this->monitorService->getMonitoredWebsites();
            $stats = $this->monitorService->getStatistics();

            return response()->json([
                'success' => true,
                'websites' => $websites,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get monitored websites: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monitored websites'
            ], 500);
        }
    }

    /**
     * Add a new website to monitor
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'name' => 'nullable|string|max:255',
            'interval' => 'nullable|integer|min:60',
            'depth' => 'nullable|integer|min:1|max:10',
            'monitor_blogs' => 'nullable|boolean',
            'monitor_pages' => 'nullable|boolean',
            'monitor_changes' => 'nullable|boolean',
        ]);

        try {
            $result = $this->monitorService->addWebsite(
                $request->url,
                $request->name,
                [
                    'interval' => $request->interval ?? 3600,
                    'depth' => $request->depth ?? 3,
                    'monitor_blogs' => $request->monitor_blogs ?? true,
                    'monitor_pages' => $request->monitor_pages ?? true,
                    'monitor_changes' => $request->monitor_changes ?? true,
                ]
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to add website: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add website: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get website details
     */
    public function show($id)
    {
        try {
            $website = \DB::table('monitored_websites')->where('id', $id)->first();

            if (!$website) {
                return response()->json([
                    'success' => false,
                    'message' => 'Website not found'
                ], 404);
            }

            $pages = $this->monitorService->getWebsitePages($id);
            $blogs = $this->monitorService->getWebsiteBlogs($id, 200);
            $changes = $this->monitorService->getWebsiteChanges($id, 100);

            return response()->json([
                'success' => true,
                'website' => $website,
                'pages' => $pages,
                'blogs' => $blogs,
                'changes' => $changes,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get website details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get website details'
            ], 500);
        }
    }

    /**
     * Remove a website from monitoring
     */
    public function destroy($id)
    {
        try {
            $result = $this->monitorService->removeWebsite($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to remove website: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove website'
            ], 500);
        }
    }

    /**
     * Check for changes on a website
     */
    public function checkChanges($id)
    {
        try {
            $result = $this->monitorService->checkForChanges($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to check changes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check changes'
            ], 500);
        }
    }

    /**
     * Check all websites for changes
     */
    public function checkAll()
    {
        try {
            $websites = \DB::table('monitored_websites')
                ->where('is_active', true)
                ->get();

            $results = [];
            foreach ($websites as $website) {
                $results[] = $this->monitorService->checkForChanges($website->id);
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'total_changes' => array_sum(array_column($results, 'change_count'))
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check all websites: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check all websites'
            ], 500);
        }
    }

    /**
     * Get monitoring statistics
     */
    public function stats()
    {
        try {
            $stats = $this->monitorService->getStatistics();
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Pause/resume monitoring
     */
    public function toggle(Request $request, $id)
    {
        $request->validate([
            'active' => 'required|boolean'
        ]);

        try {
            $result = $this->monitorService->toggleMonitoring($id, $request->active);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to toggle monitoring: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle monitoring'
            ], 500);
        }
    }

    /**
     * Get recent changes across all websites
     */
    public function recentChanges()
    {
        try {
            $changes = \DB::table('website_changes as c')
                ->join('monitored_websites as w', 'c.website_id', '=', 'w.id')
                ->select('c.*', 'w.name as website_name', 'w.url as website_url')
                ->orderBy('c.detected_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get recent changes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent changes'
            ], 500);
        }
    }

    /**
     * Real-time updates endpoint (Server-Sent Events)
     */
    public function stream(Request $request)
    {
        $response = response()->stream(function () use ($request) {
            $lastCheck = time();

            while (true) {
                // Check for new changes every 5 seconds
                if (time() - $lastCheck >= 5) {
                    $changes = \DB::table('website_changes as c')
                        ->join('monitored_websites as w', 'c.website_id', '=', 'w.id')
                        ->select('c.*', 'w.name as website_name')
                        ->where('c.detected_at', '>', \Carbon\Carbon::now()->subSeconds(10))
                        ->orderBy('c.detected_at', 'desc')
                        ->get();

                    if ($changes->count() > 0) {
                        echo "data: " . json_encode([
                            'type' => 'changes',
                            'data' => $changes
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }

                    $lastCheck = time();
                }

                // Send heartbeat
                echo ": heartbeat\n\n";
                ob_flush();
                flush();

                // Small delay
                usleep(500000); // 0.5 seconds

                // Check if connection is still alive
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200);

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
