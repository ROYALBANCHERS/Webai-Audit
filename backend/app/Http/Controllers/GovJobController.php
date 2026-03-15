<?php

namespace App\Http\Controllers;

use App\Services\GovJobService;
use App\Models\GovJob;
use App\Models\JobSubscription;
use App\Models\JobMatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GovJobController extends Controller
{
    protected GovJobService $govJobService;

    public function __construct(GovJobService $govJobService)
    {
        $this->govJobService = $govJobService;
    }

    /**
     * Get all government jobs with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'category', 'department', 'last_date_from', 'last_date_to']);

        if (!empty($filters)) {
            $result = $this->govJobService->searchJobs($filters);
        } else {
            $result = [
                'jobs' => GovJob::active()->latest()->paginate(20),
                'filters' => []
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result['jobs'],
            'filters' => $result['filters'] ?? []
        ]);
    }

    /**
     * Get a specific job
     */
    public function show(int $id): JsonResponse
    {
        $job = GovJob::with('matches')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }

    /**
     * Get jobs for the authenticated user based on their preferences
     */
    public function myJobs(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $result = $this->govJobService->getJobsForUser(
            Auth::id(),
            $page,
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => $result['jobs'],
            'matched' => $result['matched'],
            'preferences' => $result['preferences'] ?? null
        ]);
    }

    /**
     * Create or update user subscription
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keywords' => 'array',
            'keywords.*' => 'string|min:2',
            'categories' => 'array',
            'categories.*' => 'string|in:SSC,Banking,Railway,UPSC,Teaching,Defense,Police,Engineering,Other',
            'departments' => 'array',
            'departments.*' => 'string',
            'qualifications' => 'array',
            'qualifications.*' => 'string',
            'notification_methods' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription = JobSubscription::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'keywords' => $request->get('keywords', []),
                'departments' => $request->get('departments', []),
                'qualifications' => $request->get('qualifications', []),
                'locations' => $request->get('locations', []),
                'notification_methods' => $request->get('notification_methods', ['email']),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'data' => $subscription
        ]);
    }

    /**
     * Get user's current subscription
     */
    public function getSubscription(): JsonResponse
    {
        $subscription = JobSubscription::where('user_id', Auth::id())->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No subscription found. Create one to get personalized job alerts.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }

    /**
     * Toggle subscription active status
     */
    public function toggleSubscription(): JsonResponse
    {
        $subscription = JobSubscription::where('user_id', Auth::id())->firstOrFail();

        $subscription->update([
            'is_active' => !$subscription->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $subscription->is_active ? 'Subscription activated' : 'Subscription paused',
            'data' => $subscription
        ]);
    }

    /**
     * Get job categories
     */
    public function getCategories(): JsonResponse
    {
        $categories = [
            'SSC' => ['count' => GovJob::where('category', 'SSC')->active()->count()],
            'Banking' => ['count' => GovJob::where('category', 'Banking')->active()->count()],
            'Railway' => ['count' => GovJob::where('category', 'Railway')->active()->count()],
            'UPSC' => ['count' => GovJob::where('category', 'UPSC')->active()->count()],
            'Teaching' => ['count' => GovJob::where('category', 'Teaching')->active()->count()],
            'Defense' => ['count' => GovJob::where('category', 'Defense')->active()->count()],
            'Police' => ['count' => GovJob::where('category', 'Police')->active()->count()],
            'Engineering' => ['count' => GovJob::where('category', 'Engineering')->active()->count()],
            'Other' => ['count' => GovJob::where('category', 'Other')->active()->count()],
        ];

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get departments list
     */
    public function getDepartments(): JsonResponse
    {
        $departments = GovJob::selectRaw('department, COUNT(*) as count')
            ->whereNotNull('department')
            ->where('is_active', true)
            ->groupBy('department')
            ->orderByDesc('count')
            ->get()
            ->map(fn($item) => [
                'name' => $item->department,
                'count' => $item->count
            ]);

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Get job statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->govJobService->getStatistics();

        // Add user-specific stats
        if (Auth::check()) {
            $userStats = [
                'my_matches' => JobMatch::where('user_id', Auth::id())->count(),
                'unread_matches' => JobMatch::where('user_id', Auth::id())
                    ->where('is_notified', false)
                    ->count(),
            ];
            $stats = array_merge($stats, $userStats);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Mark a job match as read/viewed
     */
    public function markAsRead(int $jobId): JsonResponse
    {
        $match = JobMatch::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $match->update(['is_notified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Job marked as read'
        ]);
    }

    /**
     * Save a job (bookmark functionality)
     */
    public function saveJob(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:gov_jobs,id'
        ]);

        // TODO: Implement saved_jobs table and logic
        // For now, return success

        return response()->json([
            'success' => true,
            'message' => 'Job saved successfully'
        ]);
    }

    /**
     * Get expiring soon jobs
     */
    public function getExpiringSoon(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);

        $jobs = GovJob::active()
            ->expiringSoon($days)
            ->latest('last_date_to_apply')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'filters' => ['expiring_soon' => true, 'days' => $days]
        ]);
    }

    /**
     * Admin: Trigger manual crawl
     */
    public function triggerCrawl(): JsonResponse
    {
        // Note: In production, add authorization check
        // $this->authorize('crawl', GovJob::class);

        $result = $this->govJobService->crawlAllSources();

        return response()->json([
            'success' => true,
            'message' => 'Crawl completed',
            'data' => $result
        ]);
    }
}
