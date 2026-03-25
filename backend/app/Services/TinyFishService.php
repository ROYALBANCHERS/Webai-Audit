<?php

namespace App\Services;

use App\Models\PublicCrawlSource;
use App\Models\GovJob;
use App\Models\ScrapingLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class TinyFishService
{
    protected $agentqlApiKey;
    protected $agentqlApiEndpoint;
    protected $govJobService;

    public function __construct(GovJobService $govJobService)
    {
        $this->agentqlApiKey = env('TINYFISH_API_KEY', 'bS6r2HMf-VwhcyBARDoIxIyctP35l6zyMHc_SAC476RWDIoBjud71w');
        $this->agentqlApiEndpoint = env('TINYFISH_ENDPOINT', 'https://api.agentql.com/v1/query');
        $this->govJobService = $govJobService;
    }

    /**
     * Scrape website using TinyFish AgentQL with fallback
     */
    public function scrapeWithFallback(PublicCrawlSource $source): array
    {
        $strategies = [
            'agentql' => 'scrapeWithAgentQL',
            'guzzle' => 'scrapeWithGuzzle',
            'rss' => 'scrapeRSSFeed',
        ];

        $strategy = $source->scraping_strategy;

        // If auto, try each strategy in order
        if ($strategy === 'auto') {
            foreach ($strategies as $method => $function) {
                try {
                    Log::info("Trying {$method} for {$source->name}");
                    $result = $this->$function($source);
                    if ($result['success']) {
                        Log::info("Success with {$method} for {$source->name}");
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning("Strategy {$method} failed for {$source->name}: {$e->getMessage()}");
                    continue;
                }
            }
        } else {
            // Use specific strategy
            if (isset($strategies[$strategy])) {
                return $this->{$strategies[$strategy]}($source);
            }
        }

        // All strategies failed
        return [
            'success' => false,
            'error' => 'All scraping strategies failed',
            'jobs' => [],
            'method' => 'none'
        ];
    }

    /**
     * Scrape using AgentQL API
     */
    protected function scrapeWithAgentQL(PublicCrawlSource $source): array
    {
        $startTime = microtime(true);

        try {
            // Build AgentQL query
            $query = $this->buildAgentQLQuery($source);

            Log::info("Calling AgentQL API for {$source->url}");

            // Call AgentQL API
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->agentqlApiKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'WebAI-Audit/1.0 (+https://royalbanchers.com)',
                ])
                ->post($this->agentqlApiEndpoint, [
                    'url' => $source->url,
                    'query' => $query,
                    'options' => [
                        'waitForSelector' => $this->getWaitSelector($source),
                        'timeout' => 30000,
                        'waitFor' => 'networkidle0',
                    ]
                ]);

            if (!$response->successful()) {
                throw new \Exception("AgentQL API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            // Parse AgentQL response into jobs
            $jobs = $this->parseAgentQLResponse($data, $source);

            $duration = (microtime(true) - $startTime) * 1000;

            // Log success
            $this->logScrapingResult($source, 'agentql', count($jobs), $duration, 'success');

            return [
                'success' => true,
                'method' => 'agentql',
                'jobs' => $jobs,
                'duration_ms' => round($duration, 2),
                'source' => $source
            ];

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error("AgentQL scraping failed for {$source->url}: {$e->getMessage()}");

            // Log failure
            $this->logScrapingResult($source, 'agentql', 0, $duration, 'failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Build AgentQL query based on source configuration
     */
    protected function buildAgentQLQuery(PublicCrawlSource $source): string
    {
        // Use custom AgentQL query if provided
        if (!empty($source->agentql_query)) {
            return $source->agentql_query;
        }

        // Generate intelligent query based on category
        $queries = [
            'ssc' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Vv]acanc.*|.*[Ee]xam.*|.*[Nn]otification.*|.*[Aa]dvertisem.*"),
    department: text(".*[Ss][Ss][Cc].*|.*Staff Selection.*"),
    vacancies: number(".*\\d+.*"),
    last_date: date(".*[Ll]ast [Dd]ate.*|.*[Aa]pply.*"),
    apply_link: link(".*[Aa]pply.*|.*[Rr]egistr.*"),
    notification_link: link(".*[Nn]otif.*|.*[Pp][Dd][Ff].*"),
    qualification: text(".*[Qq]ualific.*|.*[Ee]ducat.*"),
    fee: text(".*[Ff]ee.*|.*[Pp]aymen.*")
  }
}',
            'upsc' => '{
  jobs[] {
    title: text(".*[Ee]xamin.*|.*[Rr]ecruit.*|.*[Vv]acanc.*"),
    department: text(".*[Uu][Pp][Ss][Cc].*|.*Commission.*"),
    posts: text(".*\\d+.*[Pp]ost.*|.*\\d+.*[Vv]acanc.*"),
    last_date: date(".*[Ll]ast [Dd]ate.*|.*[Cc]lose.*"),
    apply_link: link(".*[Aa]pply.*|.*[Rr]egistr.*"),
    notification: link(".*[Dd]etail.*|.*[Pp][Dd][Ff].*")
  }
}',
            'ibps' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Vv]acanc.*|.*[Ee]xam.*|.*[Cc]lerk.*|.*[Pp][Oo].*|.*[Ss][Oo].*"),
    bank: text(".*[Bb]ank.*|.*[Ii][Bb][Pp][Ss].*"),
    posts: text(".*\\d+.*"),
    last_date: date(".*[Ll]ast [Dd]ate.*"),
    apply_link: link(".*[Aa]pply.*")
  }
}',
            'railway' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Vv]acanc.*|.*[Nn]otif.*|.*[Rr][Rr][Bb].*"),
    department: text(".*[Rr]ailway.*|.*[Rr][Rr][Bb].*"),
    posts: text(".*\\d+.*"),
    last_date: date(".*[Ll]ast [Dd]ate.*"),
    apply_link: link(".*[Aa]pply.*")
  }
}',
            'defence' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Jj]ob.*|.*[Ee]ntry.*|.*[Bh]arti.*"),
    force: text(".*[Aa]rmy.*|.*[Nn]avy.*|.*[Aa]ir [Ff]orce.*|.*[Dd]efence.*"),
    posts: text(".*\\d+.*"),
    last_date: date(".*[Ll]ast [Dd]ate.*"),
    apply_link: link(".*[Aa]pply.*")
  }
}',
            'teaching' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Pp]ost.*|.*[Vv]acanc.*|.*[Tt]eacher.*|.*[Pp]rofessor.*"),
    institution: text(".*"),
    subject: text(".*"),
    last_date: date(".*[Ll]ast [Dd]ate.*"),
    apply_link: link(".*[Aa]pply.*")
  }
}',
            'state' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Vv]acanc.*|.*[Bb]harti.*|.*[Pp]ost.*"),
    department: text(".*"),
    state: text(".*"),
    last_date: date(".*[Ll]ast [Dd]ate.*|.*[Aa]pplic.*"),
    apply_link: link(".*[Aa]pply.*")
  }
}',
            'general' => '{
  jobs[] {
    title: text(".*[Rr]ecruit.*|.*[Vv]acanc.*|.*[Jj]ob.*|.*[Nn]otif.*|.*[Bb]harti.*|.*[Aa]dv.*"),
    organization: text(".*"),
    description: text(".*"),
    link: link(".*"),
    date: date(".*")
  }
}'
        ];

        return $queries[$source->category] ?? $queries['general'];
    }

    /**
     * Parse AgentQL response into GovJob format
     */
    protected function parseAgentQLResponse(array $data, PublicCrawlSource $source): array
    {
        $jobs = [];
        $rawJobs = $data['data']['jobs'] ?? [];

        if (empty($rawJobs)) {
            Log::warning("No jobs found in AgentQL response for {$source->name}");
            return [];
        }

        foreach ($rawJobs as $rawJob) {
            // Skip if no title
            if (empty($rawJob['title'])) {
                continue;
            }

            // Extract and normalize job data
            $job = [
                'title' => $this->cleanText($rawJob['title']),
                'department' => $this->cleanText($rawJob['department'] ?? $rawJob['organization'] ?? $rawJob['bank'] ?? $rawJob['institution'] ?? $source->name),
                'description' => $this->cleanText($rawJob['description'] ?? $rawJob['title'] ?? ''),
                'qualification' => $this->cleanText($rawJob['qualification'] ?? $rawJob['subject'] ?? null),
                'vacancy_count' => $this->extractVacancyCount($rawJob['vacancies'] ?? $rawJob['posts'] ?? null),
                'last_date_to_apply' => $this->normalizeDate($rawJob['last_date'] ?? $rawJob['date'] ?? null),
                'salary' => $this->cleanText($rawJob['salary'] ?? $rawJob['fee'] ?? null),
                'source_url' => $this->cleanText($rawJob['apply_link'] ?? $rawJob['link'] ?? $rawJob['notification'] ?? $source->url),
                'source_website' => $source->name,
                'category' => $source->category,
                'source_type' => 'public',
                'public_source_id' => $source->id,
                'scraping_method' => 'agentql',
                'confidence_score' => $rawJob['_confidence'] ?? 0.85,
                'is_active' => true,
                'crawled_at' => now(),
            ];

            $jobs[] = $job;
        }

        Log::info("Parsed " . count($jobs) . " jobs from AgentQL response for {$source->name}");

        return $jobs;
    }

    /**
     * Scrape using traditional Guzzle + Symfony DomCrawler
     */
    protected function scrapeWithGuzzle(PublicCrawlSource $source): array
    {
        $startTime = microtime(true);

        try {
            Log::info("Fetching page with Guzzle for {$source->url}");

            $html = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                ])
                ->get($source->url)
                ->body();

            $crawler = new Crawler($html);
            $jobs = $this->parseWithSelectors($crawler, $source);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logScrapingResult($source, 'guzzle', count($jobs), $duration, 'success');

            return [
                'success' => true,
                'method' => 'guzzle',
                'jobs' => $jobs,
                'duration_ms' => round($duration, 2),
                'source' => $source
            ];

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error("Guzzle scraping failed for {$source->url}: {$e->getMessage()}");

            $this->logScrapingResult($source, 'guzzle', 0, $duration, 'failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Parse HTML with custom selectors
     */
    protected function parseWithSelectors(Crawler $crawler, PublicCrawlSource $source): array
    {
        $jobs = [];
        $selectors = $source->selectors ?? [];

        // Use predefined selectors based on category
        $categorySelectors = $this->getCategorySelectors($source->category);
        $selectors = array_merge($categorySelectors, $selectors);

        try {
            // Find job containers
            $containerSelector = $selectors['container'] ?? '.job-item, .notice-item, .vacancy, .post, article';
            $containers = $crawler->filter($containerSelector);

            Log::info("Found {$containers->count()} job containers for {$source->name}");

            foreach ($containers as $containerNode) {
                $containerCrawler = new Crawler($containerNode);

                $titleNode = $containerCrawler->filter($selectors['title'] ?? 'h2, h3, .title, a');
                $linkNode = $containerCrawler->filter($selectors['link'] ?? 'a');
                $dateNode = $containerCrawler->filter($selectors['date'] ?? '.date, .last-date, time');

                if ($titleNode->count() > 0) {
                    $job = [
                        'title' => $this->cleanText($titleNode->first()->text()),
                        'department' => $source->name,
                        'description' => $this->cleanText($containerCrawler->filter('.description, p')->first()->text() ?? ''),
                        'source_url' => $linkNode->count() > 0 ? $linkNode->first()->link()->getUri() : $source->url,
                        'source_website' => $source->name,
                        'category' => $source->category,
                        'source_type' => 'public',
                        'public_source_id' => $source->id,
                        'scraping_method' => 'guzzle',
                        'confidence_score' => 0.70,
                        'is_active' => true,
                        'crawled_at' => now(),
                    ];

                    if ($dateNode->count() > 0) {
                        $job['last_date_to_apply'] = $this->normalizeDate($dateNode->first()->text());
                    }

                    $jobs[] = $job;
                }
            }

        } catch (\Exception $e) {
            Log::error("Error parsing with selectors: {$e->getMessage()}");
        }

        return $jobs;
    }

    /**
     * Scrape RSS feed
     */
    protected function scrapeRSSFeed(PublicCrawlSource $source): array
    {
        $startTime = microtime(true);

        try {
            $xmlContent = Http::timeout(30)->get($source->url)->body();
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                throw new \Exception("Failed to parse RSS feed");
            }

            $jobs = [];
            $items = $xml->channel->item ?? $xml->entry ?? [];

            foreach ($items as $item) {
                $job = [
                    'title' => $this->cleanText((string)$item->title),
                    'department' => $source->name,
                    'description' => $this->cleanText((string)$item->description),
                    'source_url' => (string)$item->link,
                    'source_website' => $source->name,
                    'category' => $source->category,
                    'source_type' => 'public',
                    'public_source_id' => $source->id,
                    'scraping_method' => 'rss',
                    'confidence_score' => 0.95,
                    'is_active' => true,
                    'crawled_at' => now(),
                ];

                // Try to parse date
                if (isset($item->pubDate)) {
                    $job['last_date_to_apply'] = $this->normalizeDate((string)$item->pubDate);
                }

                $jobs[] = $job;
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logScrapingResult($source, 'rss', count($jobs), $duration, 'success');

            return [
                'success' => true,
                'method' => 'rss',
                'jobs' => $jobs,
                'duration_ms' => round($duration, 2),
                'source' => $source
            ];

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logScrapingResult($source, 'rss', 0, $duration, 'failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Get predefined selectors for categories
     */
    protected function getCategorySelectors(string $category): array
    {
        $selectors = [
            'ssc' => [
                'container' => '.notice-item, .job-item, .news-item, .latest-news',
                'title' => '.notice-title, h3, .title',
                'link' => 'a',
                'date' => '.date, .notice-date, time'
            ],
            'upsc' => [
                'container' => '.recruitment-item, .vacancy',
                'title' => 'h2, h3',
                'link' => 'a[href*="recruit"]'
            ],
            'railway' => [
                'container' => '.news-item, .notice',
                'title' => 'h3, .title',
                'link' => 'a'
            ]
        ];

        return $selectors[$category] ?? [];
    }

    /**
     * Get wait selector for AgentQL
     */
    protected function getWaitSelector(PublicCrawlSource $source): string
    {
        $waitSelectors = [
            'ssc' => '.notice-item, .job-item',
            'upsc' => '.recruitment-item',
            'railway' => '.news-item',
            'ibps' => '.vacancy',
        ];

        return $waitSelectors[$source->category] ?? '.job-listing, .vacancy, .notice';
    }

    /**
     * Clean text content
     */
    protected function cleanText($text): string
    {
        if ($text === null) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', (string)$text));
    }

    /**
     * Extract vacancy count from string
     */
    protected function extractVacancyCount($value): ?int
    {
        if (!$value) {
            return null;
        }

        preg_match('/(\d+)/', (string)$value, $matches);
        return isset($matches[1]) ? (int)$matches[1] : null;
    }

    /**
     * Normalize date to Y-m-d format
     */
    protected function normalizeDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        } catch (\Exception $e) {
            Log::warning("Could not parse date: {$value}");
        }

        return null;
    }

    /**
     * Log scraping result for analytics
     */
    protected function logScrapingResult(
        PublicCrawlSource $source,
        string $method,
        int $jobsFound,
        float $duration,
        string $status,
        ?string $error = null
    ): void {
        ScrapingLog::create([
            'source_id' => $source->id,
            'url' => $source->url,
            'method_used' => $method,
            'jobs_found' => $jobsFound,
            'duration_ms' => round($duration),
            'status' => $status,
            'error_message' => $error,
            'metadata' => [
                'category' => $source->category,
                'strategy' => $source->scraping_strategy,
            ]
        ]);

        // Update source statistics
        $source->update([
            'last_crawled_at' => now(),
            'next_crawl_at' => now()->addSeconds($source->crawl_frequency),
            'total_jobs_found' => $source->total_jobs_found + $jobsFound,
            'success_count' => $status === 'success' ? $source->success_count + 1 : $source->success_count,
            'failure_count' => $status === 'failed' ? $source->failure_count + 1 : $source->failure_count,
            'status' => $status === 'success' ? 'active' : 'failed',
            'last_error' => $error
        ]);
    }

    /**
     * Save jobs from scraping results
     */
    public function saveJobs(array $jobs, PublicCrawlSource $source): int
    {
        $jobsAdded = 0;

        foreach ($jobs as $jobData) {
            try {
                $job = GovJob::updateOrCreate(
                    [
                        'source_url' => $jobData['source_url'],
                        'public_source_id' => $source->id
                    ],
                    $jobData
                );

                if ($job->wasRecentlyCreated) {
                    $jobsAdded++;
                    Log::info("New job created: {$job->title} from {$source->name}");
                }
            } catch (\Exception $e) {
                Log::error("Error saving job: {$e->getMessage()}");
            }
        }

        return $jobsAdded;
    }
}
