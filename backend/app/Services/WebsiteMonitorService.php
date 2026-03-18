<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class WebsiteMonitorService
{
    /**
     * Add a website to monitor
     */
    public function addWebsite(string $url, string $name = null, array $options = []): array
    {
        $url = $this->normalizeUrl($url);

        // Check if website already exists
        $existing = DB::table('monitored_websites')->where('url', $url)->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Website already being monitored',
                'website' => $existing
            ];
        }

        $websiteId = DB::table('monitored_websites')->insertGetId([
            'url' => $url,
            'name' => $name ?: parse_url($url, PHP_URL_HOST),
            'check_interval' => $options['interval'] ?? 3600, // Default 1 hour
            'crawl_depth' => $options['depth'] ?? 3,
            'monitor_blogs' => $options['monitor_blogs'] ?? true,
            'monitor_pages' => $options['monitor_pages'] ?? true,
            'monitor_changes' => $options['monitor_changes'] ?? true,
            'is_active' => true,
            'last_checked' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Perform initial crawl
        $this->performInitialCrawl($websiteId, $url);

        return [
            'success' => true,
            'message' => 'Website added successfully',
            'website_id' => $websiteId
        ];
    }

    /**
     * Get all monitored websites
     */
    public function getMonitoredWebsites(): array
    {
        return DB::table('monitored_websites')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($site) {
                $site->change_count = DB::table('website_changes')
                    ->where('website_id', $site->id)
                    ->where('created_at', '>', Carbon::now()->subDays(7))
                    ->count();
                return $site;
            })
            ->toArray();
    }

    /**
     * Get changes for a website
     */
    public function getWebsiteChanges(int $websiteId, int $limit = 50): array
    {
        return DB::table('website_changes')
            ->where('website_id', $websiteId)
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get all pages for a website
     */
    public function getWebsitePages(int $websiteId): array
    {
        return DB::table('website_pages')
            ->where('website_id', $websiteId)
            ->orderBy('last_seen', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get blog posts for a website
     */
    public function getWebsiteBlogs(int $websiteId, int $limit = 100): array
    {
        return DB::table('website_blogs')
            ->where('website_id', $websiteId)
            ->orderBy('discovered_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Perform initial crawl of a website
     */
    protected function performInitialCrawl(int $websiteId, string $url): void
    {
        try {
            $pages = $this->crawlWebsite($url, 3);
            $blogs = $this->extractBlogs($url, $pages);

            foreach ($pages as $page) {
                $this->savePage($websiteId, $page);
            }

            foreach ($blogs as $blog) {
                $this->saveBlog($websiteId, $blog);
            }

            DB::table('monitored_websites')
                ->where('id', $websiteId)
                ->update([
                    'last_checked' => now(),
                    'page_count' => count($pages),
                    'blog_count' => count($blogs),
                    'updated_at' => now()
                ]);

        } catch (\Exception $e) {
            Log::error("Initial crawl failed for website {$websiteId}: " . $e->getMessage());
        }
    }

    /**
     * Crawl website and return all pages
     */
    public function crawlWebsite(string $url, int $depth = 3): array
    {
        $pages = [];
        $visited = [];
        $toVisit = [$url];
        $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

        while (!empty($toVisit) && count($pages) < 500) {
            $currentUrl = array_shift($toVisit);

            if (isset($visited[$currentUrl])) {
                continue;
            }

            $visited[$currentUrl] = true;

            try {
                $response = Http::timeout(30)->get($currentUrl);

                if (!$response->successful()) {
                    continue;
                }

                $html = $response->body();
                $crawler = new Crawler($html);

                // Extract page info
                $title = $crawler->filter('title')->first()->text('') ?: 'No title';
                $description = $crawler->filter('meta[name="description"]')->first()->attr('content', '');
                $contentHash = md5($this->extractMainContent($crawler));

                $pages[] = [
                    'url' => $currentUrl,
                    'title' => $title,
                    'description' => $description,
                    'content_hash' => $contentHash,
                    'status_code' => $response->status(),
                ];

                // Extract links for further crawling if depth not reached
                if (count($pages) < 500 && $depth > 0) {
                    $links = $crawler->filter('a[href]')->each(function ($node) use ($baseUrl) {
                        $href = $node->attr('href');
                        return $this->normalizeUrl($href, $baseUrl);
                    });

                    foreach ($links as $link) {
                        if ($this->isInternalUrl($link, $baseUrl) && !isset($visited[$link])) {
                            $toVisit[] = $link;
                        }
                    }
                }

                // Rate limiting
                usleep(100000); // 0.1 second delay

            } catch (\Exception $e) {
                Log::error("Failed to crawl {$currentUrl}: " . $e->getMessage());
            }
        }

        return $pages;
    }

    /**
     * Extract blog posts from pages
     */
    protected function extractBlogs(string $url, array $pages): array
    {
        $blogs = [];
        $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

        foreach ($pages as $page) {
            try {
                $response = Http::timeout(30)->get($page['url']);

                if (!$response->successful()) {
                    continue;
                }

                $crawler = new Crawler($response->body());

                // Try different blog selectors
                $selectors = [
                    'article',
                    '[class*="blog"]',
                    '[class*="post"]',
                    '[id*="blog"]',
                    '[id*="post"]',
                    '.news-item',
                    '.article-item',
                    'article.post',
                    'div[type="http://schema.org/BlogPosting"]',
                ];

                $articles = null;
                foreach ($selectors as $selector) {
                    try {
                        $articles = $crawler->filter($selector);
                        if ($articles->count() > 0) {
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if ($articles && $articles->count() > 0) {
                    $articles->each(function ($node) use (&$blogs, $baseUrl) {
                        try {
                            $titleNode = $node->filter('h1, h2, h3, h4, h5, h6')->first();
                            $linkNode = $node->filter('a[href]')->first();
                            $dateNode = $node->filter('[datetime], time, [class*="date"], [class*="time"]')->first();

                            if ($linkNode) {
                                $blogUrl = $this->normalizeUrl($linkNode->attr('href'), $baseUrl);
                                $title = $titleNode ? $titleNode->text() : $linkNode->text();
                                $date = $dateNode ? $dateNode->attr('datetime') ?: $dateNode->text() : null;

                                $blogs[] = [
                                    'url' => $blogUrl,
                                    'title' => trim($title),
                                    'publish_date' => $date,
                                    'content_hash' => md5($title . $blogUrl),
                                ];
                            }
                        } catch (\Exception $e) {
                            // Skip this article
                        }
                    });
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        // Remove duplicates
        $uniqueBlogs = [];
        $seen = [];
        foreach ($blogs as $blog) {
            $key = $blog['url'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueBlogs[] = $blog;
            }
        }

        return $uniqueBlogs;
    }

    /**
     * Check for changes on a monitored website
     */
    public function checkForChanges(int $websiteId): array
    {
        $website = DB::table('monitored_websites')->where('id', $websiteId)->first();

        if (!$website || !$website->is_active) {
            return ['success' => false, 'message' => 'Website not found or inactive'];
        }

        $changes = [];

        try {
            // Crawl current state
            $currentPages = $this->crawlWebsite($website->url, $website->crawl_depth ?? 3);
            $currentBlogs = $this->extractBlogs($website->url, $currentPages);

            // Check for new/changed pages
            foreach ($currentPages as $page) {
                $existingPage = DB::table('website_pages')
                    ->where('website_id', $websiteId)
                    ->where('url', $page['url'])
                    ->first();

                if (!$existingPage) {
                    // New page found
                    $this->savePage($websiteId, $page);
                    $this->recordChange($websiteId, 'new_page', $page['url'], "New page: {$page['title']}");
                    $changes[] = [
                        'type' => 'new_page',
                        'url' => $page['url'],
                        'title' => $page['title'],
                        'message' => "New page discovered: {$page['title']}"
                    ];
                } elseif ($existingPage->content_hash !== $page['content_hash']) {
                    // Page changed
                    $this->savePage($websiteId, $page);
                    $this->recordChange($websiteId, 'page_changed', $page['url'], "Page updated: {$page['title']}");
                    $changes[] = [
                        'type' => 'page_changed',
                        'url' => $page['url'],
                        'title' => $page['title'],
                        'message' => "Page content changed: {$page['title']}"
                    ];
                }
            }

            // Check for new blogs
            foreach ($currentBlogs as $blog) {
                $existingBlog = DB::table('website_blogs')
                    ->where('website_id', $websiteId)
                    ->where('url', $blog['url'])
                    ->first();

                if (!$existingBlog) {
                    $this->saveBlog($websiteId, $blog);
                    $this->recordChange($websiteId, 'new_blog', $blog['url'], "New blog: {$blog['title']}");
                    $changes[] = [
                        'type' => 'new_blog',
                        'url' => $blog['url'],
                        'title' => $blog['title'],
                        'message' => "New blog post: {$blog['title']}"
                    ];
                } elseif ($existingBlog->title !== $blog['title']) {
                    DB::table('website_blogs')
                        ->where('id', $existingBlog->id)
                        ->update(['title' => $blog['title'], 'updated_at' => now()]);
                    $this->recordChange($websiteId, 'blog_updated', $blog['url'], "Blog updated: {$blog['title']}");
                    $changes[] = [
                        'type' => 'blog_updated',
                        'url' => $blog['url'],
                        'title' => $blog['title'],
                        'old_title' => $existingBlog->title,
                        'message' => "Blog post updated: {$blog['title']}"
                    ];
                }
            }

            // Update last checked time
            DB::table('monitored_websites')
                ->where('id', $websiteId)
                ->update([
                    'last_checked' => now(),
                    'page_count' => count($currentPages),
                    'blog_count' => DB::table('website_blogs')->where('website_id', $websiteId)->count(),
                    'updated_at' => now()
                ]);

        } catch (\Exception $e) {
            Log::error("Change check failed for website {$websiteId}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'changes' => $changes,
            'change_count' => count($changes)
        ];
    }

    /**
     * Get monitoring statistics
     */
    public function getStatistics(): array
    {
        $totalWebsites = DB::table('monitored_websites')->where('is_active', true)->count();
        $totalPages = DB::table('website_pages')->count();
        $totalBlogs = DB::table('website_blogs')->count();
        $totalChanges = DB::table('website_changes')
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->count();

        $recentChanges = DB::table('website_changes as c')
            ->join('monitored_websites as w', 'c.website_id', '=', 'w.id')
            ->select('c.*', 'w.name as website_name', 'w.url as website_url')
            ->orderBy('c.detected_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        return [
            'total_websites' => $totalWebsites,
            'total_pages' => $totalPages,
            'total_blogs' => $totalBlogs,
            'recent_changes' => $totalChanges,
            'latest_changes' => $recentChanges,
        ];
    }

    /**
     * Save page to database
     */
    protected function savePage(int $websiteId, array $page): void
    {
        DB::table('website_pages')->insertOrIgnore([
            'website_id' => $websiteId,
            'url' => $page['url'],
            'title' => $page['title'] ?? '',
            'description' => $page['description'] ?? '',
            'content_hash' => $page['content_hash'],
            'status_code' => $page['status_code'] ?? 200,
            'first_seen' => now(),
            'last_seen' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Save blog to database
     */
    protected function saveBlog(int $websiteId, array $blog): void
    {
        DB::table('website_blogs')->insertOrIgnore([
            'website_id' => $websiteId,
            'url' => $blog['url'],
            'title' => $blog['title'] ?? '',
            'publish_date' => $blog['publish_date'] ?? null,
            'content_hash' => $blog['content_hash'] ?? md5($blog['url']),
            'discovered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Record a change
     */
    protected function recordChange(int $websiteId, string $changeType, string $url, string $message): void
    {
        DB::table('website_changes')->insert([
            'website_id' => $websiteId,
            'change_type' => $changeType,
            'url' => $url,
            'description' => $message,
            'detected_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Normalize URL
     */
    protected function normalizeUrl(string $url, string $baseUrl = null): string
    {
        $url = trim($url);

        // If relative URL, make it absolute
        if ($baseUrl && !preg_match('/^https?:\/\//i', $url)) {
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            } elseif (strpos($url, '/') === 0) {
                $url = rtrim($baseUrl, '/') . $url;
            } else {
                $url = rtrim($baseUrl, '/') . '/' . $url;
            }
        }

        // Remove fragment
        $url = preg_replace('/#.*$/', '', $url);

        return $url;
    }

    /**
     * Check if URL is internal to base URL
     */
    protected function isInternalUrl(string $url, string $baseUrl): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);

        return $urlHost === $baseHost;
    }

    /**
     * Extract main content from crawler
     */
    protected function extractMainContent(Crawler $crawler): string
    {
        // Try to find main content area
        $selectors = [
            'main',
            '[role="main"]',
            'article',
            '#content',
            '.content',
            '#main',
            '.main-content',
        ];

        foreach ($selectors as $selector) {
            try {
                $content = $crawler->filter($selector)->first()->html();
                if (!empty($content)) {
                    return $this->cleanContent($content);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback to body
        try {
            return $this->cleanContent($crawler->filter('body')->first()->html());
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Clean content for hashing
     */
    protected function cleanContent(string $html): string
    {
        // Remove scripts, styles, and comments
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        $html = strip_tags($html);
        $html = trim($html);

        return $html;
    }

    /**
     * Remove a website from monitoring
     */
    public function removeWebsite(int $websiteId): array
    {
        DB::table('monitored_websites')->where('id', $websiteId)->delete();
        DB::table('website_pages')->where('website_id', $websiteId)->delete();
        DB::table('website_blogs')->where('website_id', $websiteId)->delete();
        DB::table('website_changes')->where('website_id', $websiteId)->delete();

        return ['success' => true, 'message' => 'Website removed successfully'];
    }

    /**
     * Pause/resume monitoring
     */
    public function toggleMonitoring(int $websiteId, bool $active): array
    {
        DB::table('monitored_websites')
            ->where('id', $websiteId)
            ->update(['is_active' => $active, 'updated_at' => now()]);

        return [
            'success' => true,
            'message' => $active ? 'Monitoring resumed' : 'Monitoring paused'
        ];
    }
}
