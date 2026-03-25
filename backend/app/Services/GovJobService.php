<?php

namespace App\Services;

use App\Models\GovJob;
use App\Models\JobSubscription;
use App\Models\JobMatch;
use App\Models\CrawlSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class GovJobService
{
    protected $crawlerService;

    public function __construct(CrawlerService $crawlerService)
    {
        $this->crawlerService = $crawlerService;
    }

    /**
     * Crawl all active government job sources
     */
    public function crawlAllSources(): array
    {
        $sources = CrawlSource::where('is_active', true)->get();
        $results = [
            'success' => true,
            'total_jobs_added' => 0,
            'sources_crawled' => 0,
            'errors' => []
        ];

        foreach ($sources as $source) {
            try {
                $jobsAdded = $this->crawlSource($source);
                $results['total_jobs_added'] += $jobsAdded;
                $results['sources_crawled']++;

                // Update last crawled time
                $source->update(['last_crawled_at' => now()]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'source' => $source->name,
                    'error' => $e->getMessage()
                ];
                Log::error("Failed to crawl {$source->name}: " . $e->getMessage());
            }
        }

        // Match new jobs with user subscriptions
        if ($results['total_jobs_added'] > 0) {
            $this->matchJobsWithSubscriptions();
        }

        return $results;
    }

    /**
     * Crawl a specific source
     */
    public function crawlSource(CrawlSource $source): int
    {
        $selectors = json_decode($source->selectors, true);
        $crawlUrls = json_decode($source->crawl_urls, true);
        $jobsAdded = 0;

        foreach ($crawlUrls as $url) {
            try {
                $html = $this->fetchPage($url);
                $jobs = $this->parseJobsFromHtml($html, $selectors, $source);

                foreach ($jobs as $jobData) {
                    $job = GovJob::updateOrCreate(
                        ['source_url' => $jobData['source_url']],
                        array_merge($jobData, ['source_website' => $source->name])
                    );

                    if ($job->wasRecentlyCreated) {
                        $jobsAdded++;
                    }
                }

            } catch (\Exception $e) {
                Log::error("Failed to crawl {$url}: " . $e->getMessage());
            }
        }

        return $jobsAdded;
    }

    /**
     * Fetch page content
     */
    protected function fetchPage(string $url): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}: Failed to fetch {$url}");
        }

        return $response->body();
    }

    /**
     * Parse jobs from HTML
     */
    protected function parseJobsFromHtml(string $html, array $selectors, CrawlSource $source): array
    {
        $crawler = new Crawler($html);
        $jobs = [];

        // RSS Feed parsing
        if (isset($selectors['title']) && str_contains($selectors['title'], 'item')) {
            return $this->parseRssFeed($html, $source);
        }

        // HTML parsing
        $crawler->filter('body')->each(function (Crawler $node) use (&$jobs, $selectors, $source) {
            // Custom parsing based on selectors
            // This is a simplified example - customize based on actual website structure
        });

        return $jobs;
    }

    /**
     * Parse RSS Feed
     */
    protected function parseRssFeed(string $html, CrawlSource $source): array
    {
        $jobs = [];
        $xml = simplexml_load_string($html);

        if ($xml === false) {
            return $jobs;
        }

        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $description = strip_tags((string)$item->description);
            $pubDate = isset($item->pubDate) ? date('Y-m-d', strtotime($item->pubDate)) : null;

            // Extract last date from description
            $lastDate = $this->extractLastDate($title . ' ' . $description);

            // Categorize job
            $category = $this->categorizeJob($title);

            $jobs[] = [
                'title' => $title,
                'description' => substr($description, 0, 1000),
                'source_url' => $link,
                'last_date_to_apply' => $lastDate,
                'category' => $category,
                'department' => $this->extractDepartment($title),
            ];
        }

        return $jobs;
    }

    /**
     * Extract last date to apply from text
     */
    protected function extractLastDate(string $text): ?string
    {
        // Match patterns like "Last Date: 31-12-2024", "Apply before: 25/01/2025"
        $patterns = [
            '/(?:last date|deadline|closing)[^0-9]*([0-9]{1,2}[-\/][0-9]{1,2}[-\/][0-9]{2,4})/i',
            '/(?:apply before|due by)[^0-9]*([0-9]{1,2}[-\/][0-9]{1,2}[-\/][0-9]{2,4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = str_replace('/', '-', $matches[1]);
                $timestamp = strtotime($date);

                if ($timestamp !== false && $timestamp > time()) {
                    return date('Y-m-d', $timestamp);
                }
            }
        }

        return null;
    }

    /**
     * Categorize job based on title keywords
     */
    protected function categorizeJob(string $title): string
    {
        $categories = [
            'SSC' => ['ssc', 'staff selection', 'multitasking', 'chsl', 'cgl'],
            'Banking' => ['bank', 'ibps', 'po', 'clerk', 'rbi', 'sbi'],
            'Railway' => ['railway', 'rrb', 'rpf', 'group d', 'ntpc'],
            'UPSC' => ['upsc', 'civil services', 'ias', 'ips', 'ifs'],
            'Teaching' => ['teacher', 'professor', 'lecturer', 'tet', 'ctet'],
            'Defense' => ['army', 'navy', 'air force', 'bhartiya', 'agniveer'],
            'Police' => ['police', 'constable', 'si'],
            'Engineering' => ['engineer', 'jee', 'gate'],
        ];

        $titleLower = strtolower($title);

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($titleLower, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    /**
     * Extract department from title
     */
    protected function extractDepartment(string $title): ?string
    {
        $departments = [
            'SSC', 'UPSC', 'IBPS', 'SBI', 'RBI', 'RRB',
            'Indian Army', 'Indian Navy', 'IAF', 'CRPF', 'BSF'
        ];

        foreach ($departments as $dept) {
            if (stripos($title, $dept) !== false) {
                return $dept;
            }
        }

        return null;
    }

    /**
     * Match jobs with user subscriptions
     */
    protected function matchJobsWithSubscriptions(): int
    {
        $matchesCreated = 0;

        // Get recent jobs (last 24 hours)
        $recentJobs = GovJob::where('created_at', '>=', now()->subDay())
            ->where('is_active', true)
            ->get();

        if ($recentJobs->isEmpty()) {
            return 0;
        }

        // Get all active subscriptions
        $subscriptions = JobSubscription::where('is_active', true)
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            $preferences = json_decode($subscription->keywords, true) ?? [];

            foreach ($recentJobs as $job) {
                // Check if this job matches user preferences
                if ($this->isJobMatch($job, $preferences)) {
                    $matchScore = $this->calculateMatchScore($job, $preferences);

                    // Create match record
                    $jobMatch = JobMatch::updateOrCreate(
                        [
                            'job_id' => $job->id,
                            'user_id' => $subscription->user_id
                        ],
                        [
                            'match_score' => $matchScore,
                            'is_notified' => false
                        ]
                    );

                    if ($jobMatch->wasRecentlyCreated) {
                        $matchesCreated++;
                    }
                }
            }
        }

        // Send notifications for new matches
        if ($matchesCreated > 0) {
            $this->sendNotificationsForNewMatches();
        }

        return $matchesCreated;
    }

    /**
     * Check if job matches user preferences
     */
    protected function isJobMatch(GovJob $job, array $preferences): bool
    {
        $jobText = strtolower($job->title . ' ' . $job->description . ' ' . ($job->category ?? ''));

        // Check if any preferred keyword matches
        if (isset($preferences['keywords']) && !empty($preferences['keywords'])) {
            foreach ($preferences['keywords'] as $keyword) {
                if (str_contains($jobText, strtolower($keyword))) {
                    return true;
                }
            }
        }

        // Check category match
        if (isset($preferences['categories']) && !empty($preferences['categories'])) {
            if (in_array($job->category, $preferences['categories'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate match score for relevance ranking
     */
    protected function calculateMatchScore(GovJob $job, array $preferences): int
    {
        $score = 0;
        $jobText = strtolower($job->title . ' ' . $job->description);

        // Keyword matches
        if (isset($preferences['keywords'])) {
            foreach ($preferences['keywords'] as $keyword) {
                if (str_contains($jobText, strtolower($keyword))) {
                    $score += 10;
                }
            }
        }

        // Category match
        if (isset($preferences['categories']) && in_array($job->category, $preferences['categories'])) {
            $score += 20;
        }

        // Department match
        if (isset($preferences['departments']) && in_array($job->department, $preferences['departments'])) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Send notifications for new job matches
     * Implements email notification system
     */
    protected function sendNotificationsForNewMatches(): void
    {
        $unnotifiedMatches = JobMatch::with(['job', 'user'])
            ->where('is_notified', false)
            ->get();

        foreach ($unnotifiedMatches as $match) {
            try {
                // Send email notification if user has enabled notifications
                if ($match->user && $match->user->email && $this->shouldSendNotification($match->user)) {
                    $this->sendJobMatchEmail($match);
                    Log::info("Job notification sent to user {$match->user_id} for job {$match->job_id}");
                }

                // Mark as notified
                $match->update([
                    'is_notified' => true,
                    'notified_at' => now()
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to send notification for match {$match->id}: " . $e->getMessage());
                // Don't rethrow - continue with other notifications
            }
        }
    }

    /**
     * Check if user should receive notifications
     */
    protected function shouldSendNotification($user): bool
    {
        // Check user preferences from subscription
        $subscription = JobSubscription::where('user_id', $user->id)->first();

        if (!$subscription || !$subscription->is_active) {
            return false;
        }

        // Check notification methods
        $methods = json_decode($subscription->notification_methods, true) ?? ['email'];
        return in_array('email', $methods);
    }

    /**
     * Send job match email notification
     */
    protected function sendJobMatchEmail(JobMatch $match): void
    {
        $job = $match->job;
        $user = $match->user;

        // Build email data
        $emailData = [
            'job_title' => $job->title,
            'job_url' => $job->source_url,
            'department' => $job->department,
            'category' => $job->category,
            'last_date' => $job->last_date_to_apply,
            'match_score' => $match->match_score,
            'unsubscribe_url' => route('unsubscribe.notifications', $user->id),
        ];

        // Use Laravel's Mail facade if configured
        if (config('mail.enabled') && class_exists(\Illuminate\Support\Facades\Mail::class)) {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\NewJobMatchMail($emailData));
        } else {
            // Fallback: log for debugging or use alternative notification service
            Log::info("Email notification (not sent - mail disabled): " . json_encode($emailData));

            // Alternative: Queue for later processing
            // or integrate with email service like SendGrid, Mailgun, etc.
        }
    }

    /**
     * Get jobs for a specific user based on their preferences
     */
    public function getJobsForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $subscription = JobSubscription::where('user_id', $userId)->first();

        if (!$subscription) {
            // Return latest jobs if no subscription
            return [
                'jobs' => GovJob::latest()->paginate($perPage),
                'matched' => false
            ];
        }

        $preferences = json_decode($subscription->keywords, true) ?? [];
        $jobIds = JobMatch::where('user_id', $userId)
            ->pluck('job_id');

        $jobs = GovJob::whereIn('id', $jobIds)
            ->with('matches')
            ->orderByDesc(
                JobMatch::select('match_score')
                    ->whereColumn('gov_jobs.id', 'job_matches.job_id')
                    ->where('user_id', $userId)
                    ->limit(1)
            )
            ->paginate($perPage);

        return [
            'jobs' => $jobs,
            'matched' => true,
            'preferences' => $preferences
        ];
    }

    /**
     * Search jobs by keyword, category, or department
     * Uses parameterized queries to prevent SQL injection
     */
    public function searchJobs(array $filters): array
    {
        $query = GovJob::where('is_active', true);

        // Keyword search - uses parameterized binding via Laravel's ORM
        if (isset($filters['keyword'])) {
            $keyword = strip_tags($filters['keyword']); // Sanitize input
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', '%' . addslashes($keyword) . '%')
                  ->orWhere('description', 'like', '%' . addslashes($keyword) . '%');
            });
        }

        // Category filter - whitelist validation
        if (isset($filters['category'])) {
            $allowedCategories = ['SSC', 'Banking', 'Railway', 'UPSC', 'Teaching', 'Defense', 'Police', 'Engineering', 'Other'];
            $category = $filters['category'];
            if (in_array($category, $allowedCategories, true)) {
                $query->where('category', $category);
            }
        }

        // Department filter - sanitized
        if (isset($filters['department'])) {
            $query->where('department', strip_tags($filters['department']));
        }

        // Last date filter - validated date format
        if (isset($filters['last_date_from'])) {
            $date = $this->validateDate($filters['last_date_from']);
            if ($date) {
                $query->where('last_date_to_apply', '>=', $date);
            }
        }

        if (isset($filters['last_date_to'])) {
            $date = $this->validateDate($filters['last_date_to']);
            if ($date) {
                $query->where('last_date_to_apply', '<=', $date);
            }
        }

        return [
            'jobs' => $query->latest()->paginate(20),
            'filters' => array_intersect_key($filters, array_flip(['keyword', 'category', 'department', 'last_date_from', 'last_date_to']))
        ];
    }

    /**
     * Validate and sanitize date input
     */
    protected function validateDate(string $date): ?string
    {
        $parsed = date_parse($date);
        if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
            return date('Y-m-d', strtotime($date));
        }
        return null;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_jobs' => GovJob::where('is_active', true)->count(),
            'jobs_this_week' => GovJob::where('created_at', '>=', now()->subWeek())->count(),
            'jobs_today' => GovJob::where('created_at', '>=', now()->startOfDay())->count(),
            'expiring_soon' => GovJob::whereBetween('last_date_to_apply', [now(), now()->addWeek()])->count(),
            'by_category' => GovJob::selectRaw('category, COUNT(*) as count')
                ->where('is_active', true)
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category')
                ->toArray(),
        ];
    }
}
