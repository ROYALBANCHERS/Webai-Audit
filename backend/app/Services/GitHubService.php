<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    protected ?string $token;
    protected string $apiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->token = config('auditor.github.token') ?? env('GITHUB_TOKEN');
        $this->apiUrl = config('auditor.github.api_url', 'https://api.github.com') ?? 'https://api.github.com';
        $this->timeout = config('auditor.github.timeout', 30) ?? 30;
    }

    /**
     * Search GitHub repositories
     */
    public function searchRepositories(array $keywords, ?string $language = null): array
    {
        $query = implode(' ', $keywords);

        if ($language) {
            $query .= " language:{$language}";
        }

        try {
            $response = $this->getRequest('/search/repositories', [
                'q' => $query,
                'sort' => 'stars',
                'order' => 'desc',
                'per_page' => 20,
            ]);

            if (!$response->successful()) {
                return ['error' => 'GitHub API request failed', 'status' => $response->status()];
            }

            $data = $response->json();

            return [
                'total_count' => $data['total_count'] ?? 0,
                'repositories' => $this->formatRepositories($data['items'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error("GitHub search error: {$e->getMessage()}");
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get repository details
     */
    public function getRepositoryDetails(string $owner, string $repo): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}");

            if (!$response->successful()) {
                return ['error' => 'Repository not found', 'status' => $response->status()];
            }

            $data = $response->json();

            return [
                'id' => $data['id'],
                'name' => $data['name'],
                'full_name' => $data['full_name'],
                'owner' => [
                    'login' => $data['owner']['login'],
                    'avatar_url' => $data['owner']['avatar_url'],
                    'type' => $data['owner']['type'],
                ],
                'description' => $data['description'],
                'language' => $data['language'],
                'languages' => $this->getRepositoryLanguages($owner, $repo),
                'stars' => $data['stargazers_count'],
                'watchers' => $data['watchers_count'],
                'forks' => $data['forks_count'],
                'open_issues' => $data['open_issues_count'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'homepage' => $data['homepage'],
                'size' => $data['size'],
                'license' => $data['license'] ? $data['license']['name'] : null,
                'topics' => $data['topics'] ?? [],
                'has_wiki' => $data['has_wiki'],
                'has_pages' => $data['has_pages'],
                'url' => $data['html_url'],
                'api_url' => $data['url'],
            ];
        } catch (\Exception $e) {
            Log::error("GitHub repository details error: {$e->getMessage()}");
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get repository languages
     */
    public function getRepositoryLanguages(string $owner, string $repo): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}/languages");

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Find similar projects based on tech stack
     */
    public function getSimilarProjects(array $techStack): array
    {
        $similarProjects = [];

        // Map tech stack to GitHub languages
        $languageMap = [
            'React' => ['react', 'javascript'],
            'Vue.js' => ['vue', 'javascript'],
            'Angular' => ['angular', 'typescript'],
            'Next.js' => ['next.js', 'javascript'],
            'Nuxt.js' => ['nuxt', 'javascript'],
            'Laravel' => ['laravel', 'php'],
            'WordPress' => ['wordpress', 'php'],
            'Django' => ['django', 'python'],
            'Ruby on Rails' => ['rails', 'ruby'],
            'Express' => ['express', 'node.js'],
            'Spring' => ['spring-boot', 'java'],
            'ASP.NET' => ['asp.net', 'c#'],
        ];

        $searchTerms = [];

        foreach ($techStack['frameworks'] ?? [] as $framework) {
            $name = $framework['name'];
            if (isset($languageMap[$name])) {
                $searchTerms = array_merge($searchTerms, $languageMap[$name]);
            }
        }

        // Search for each term
        foreach (array_unique($searchTerms) as $term) {
            $results = $this->searchRepositories(['web', 'app', $term]);
            if (isset($results['repositories'])) {
                $similarProjects = array_merge($similarProjects, $results['repositories']);
            }
        }

        // Remove duplicates based on ID
        $uniqueProjects = [];
        $seenIds = [];

        foreach ($similarProjects as $project) {
            if (!in_array($project['id'], $seenIds)) {
                $seenIds[] = $project['id'];
                $uniqueProjects[] = $project;
            }
        }

        return array_slice($uniqueProjects, 0, 10);
    }

    /**
     * Analyze a GitHub repository URL
     */
    public function analyzeRepository(string $url): array
    {
        // Parse GitHub URL
        if (!preg_match('/github\.com\/([^\/]+)\/([^\/]+)/', $url, $matches)) {
            return ['error' => 'Invalid GitHub repository URL'];
        }

        $owner = $matches[1];
        $repo = $matches[2];

        // Get repository details
        $details = $this->getRepositoryDetails($owner, $repo);

        if (isset($details['error'])) {
            return $details;
        }

        // Get additional information
        $readme = $this->getRepositoryReadme($owner, $repo);
        $contributors = $this->getRepositoryContributors($owner, $repo);
        $releases = $this->getRepositoryReleases($owner, $repo);

        return [
            'repository' => $details,
            'readme' => $readme,
            'contributors' => $contributors,
            'releases' => $releases,
            'activity_score' => $this->calculateActivityScore($details, $contributors, $releases),
        ];
    }

    /**
     * Get repository README
     */
    public function getRepositoryReadme(string $owner, string $repo): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}/readme");

            if ($response->successful()) {
                $data = $response->json();

                // Decode base64 content
                $content = base64_decode($data['content']);

                return [
                    'html_url' => $data['html_url'],
                    'content' => substr($content, 0, 5000), // First 5000 chars
                    'size' => $data['size'],
                ];
            }

            return ['error' => 'No README found'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get repository contributors
     */
    public function getRepositoryContributors(string $owner, string $repo): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}/contributors", ['per_page' => 10]);

            if ($response->successful()) {
                $contributors = [];
                foreach ($response->json() as $contributor) {
                    $contributors[] = [
                        'login' => $contributor['login'],
                        'avatar_url' => $contributor['avatar_url'],
                        'contributions' => $contributor['contributions'],
                        'url' => $contributor['html_url'],
                    ];
                }
                return $contributors;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get repository releases
     */
    public function getRepositoryReleases(string $owner, string $repo): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}/releases", ['per_page' => 5]);

            if ($response->successful()) {
                $releases = [];
                foreach ($response->json() as $release) {
                    $releases[] = [
                        'tag_name' => $release['tag_name'],
                        'name' => $release['name'],
                        'created_at' => $release['created_at'],
                        'url' => $release['html_url'],
                    ];
                }
                return $releases;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get trending repositories
     */
    public function getTrending(?string $language = null, string $since = 'monthly'): array
    {
        // Note: GitHub's trending API is not officially documented
        // This uses the search API as an alternative

        $dateMap = [
            'daily' => 'daily',
            'weekly' => 'weekly',
            'monthly' => 'monthly',
        ];

        $query = 'stars:>100';

        if ($language) {
            $query .= " language:{$language}";
        }

        try {
            $response = $this->getRequest('/search/repositories', [
                'q' => $query,
                'sort' => 'stars',
                'order' => 'desc',
                'per_page' => 25,
            ]);

            if ($response->successful()) {
                return [
                    'period' => $dateMap[$since] ?? 'monthly',
                    'language' => $language,
                    'repositories' => $this->formatRepositories($response->json()['items'] ?? []),
                ];
            }

            return ['error' => 'Failed to fetch trending repositories'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search for repositories similar to a website
     */
    public function searchSimilarToWebsite(string $url, array $techStack): array
    {
        // Extract keywords from URL
        $domain = parse_url($url, PHP_URL_HOST);
        $domainParts = explode('.', $domain);
        $name = $domainParts[0] ?? '';

        $keywords = [$name, 'web', 'app'];

        // Add tech stack keywords
        foreach ($techStack['frameworks'] ?? [] as $framework) {
            $keywords[] = strtolower($framework['name']);
        }

        // Search repositories
        $results = $this->searchRepositories($keywords);

        if (isset($results['error'])) {
            return $results;
        }

        // Enhance with similarity scores
        $results['repositories'] = array_map(function ($repo) use ($techStack) {
            $repo['similarity_score'] = $this->calculateSimilarity($repo, $techStack);
            return $repo;
        }, $results['repositories']);

        // Sort by similarity score
        usort($results['repositories'], function ($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return $results;
    }

    /**
     * Get repository issues
     */
    public function getRepositoryIssues(string $owner, string $repo, string $state = 'open'): array
    {
        try {
            $response = $this->getRequest("/repos/{$owner}/{$repo}/issues", [
                'state' => $state,
                'per_page' => 25,
                'sort' => 'created',
                'direction' => 'desc',
            ]);

            if ($response->successful()) {
                $issues = [];
                foreach ($response->json() as $issue) {
                    $issues[] = [
                        'id' => $issue['id'],
                        'number' => $issue['number'],
                        'title' => $issue['title'],
                        'state' => $issue['state'],
                        'created_at' => $issue['created_at'],
                        'url' => $issue['html_url'],
                        'user' => [
                            'login' => $issue['user']['login'],
                            'avatar_url' => $issue['user']['avatar_url'],
                        ],
                        'labels' => array_map(fn($l) => $l['name'], $issue['labels'] ?? []),
                    ];
                }
                return $issues;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate activity score for a repository
     */
    protected function calculateActivityScore(array $details, array $contributors, array $releases): int
    {
        $score = 0;

        // Base score from stars (capped at 50 points)
        $score += min(50, floor(log($details['stars'] + 1) * 3));

        // Contributors (max 20 points)
        $score += min(20, count($contributors) * 2);

        // Recent releases (max 15 points)
        $recentReleases = 0;
        $now = time();
        foreach ($releases as $release) {
            $releaseTime = strtotime($release['created_at']);
            if (($now - $releaseTime) < 90 * 24 * 60 * 60) { // 90 days
                $recentReleases++;
            }
        }
        $score += min(15, $recentReleases * 5);

        // Open issues activity (max 10 points)
        if ($details['open_issues'] > 0) {
            $score += min(10, floor($details['open_issues'] / 10));
        }

        // Recently updated (max 5 points)
        $updatedTime = strtotime($details['updated_at']);
        if (($now - $updatedTime) < 30 * 24 * 60 * 60) { // 30 days
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Calculate similarity between repository and tech stack
     */
    protected function calculateSimilarity(array $repo, array $techStack): float
    {
        $score = 0.0;
        $repoLanguage = strtolower($repo['language'] ?? '');
        $repoTopics = array_map('strtolower', $repo['topics'] ?? []);

        // Check framework match
        foreach ($techStack['frameworks'] ?? [] as $framework) {
            $fwName = strtolower($framework['name']);

            // Direct language match
            if ($this->languageMatchesFramework($repoLanguage, $fwName)) {
                $score += 0.4;
            }

            // Topic match
            if (in_array($fwName, $repoTopics) || in_array(str_replace('.', '', $fwName), $repoTopics)) {
                $score += 0.3;
            }
        }

        // Check library matches
        foreach ($techStack['libraries'] ?? [] as $library) {
            $libName = strtolower($library['name']);
            if (in_array($libName, $repoTopics)) {
                $score += 0.1;
            }
        }

        return min(1.0, $score);
    }

    /**
     * Check if programming language matches framework
     */
    protected function languageMatchesFramework(string $language, string $framework): bool
    {
        $matches = [
            'javascript' => ['react', 'vue.js', 'angular', 'next.js', 'nuxt.js', 'express'],
            'typescript' => ['angular', 'next.js'],
            'php' => ['laravel', 'wordpress'],
            'python' => ['django', 'flask'],
            'ruby' => ['ruby on rails'],
            'java' => ['spring'],
            'c#' => ['asp.net'],
        ];

        foreach ($matches as $lang => $frameworks) {
            if ($language === $lang && in_array($framework, $frameworks)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format repository data
     */
    protected function formatRepositories(array $repositories): array
    {
        return array_map(function ($repo) {
            return [
                'id' => $repo['id'],
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'owner' => [
                    'login' => $repo['owner']['login'],
                    'avatar_url' => $repo['owner']['avatar_url'],
                ],
                'description' => $repo['description'],
                'language' => $repo['language'],
                'stars' => $repo['stargazers_count'],
                'forks' => $repo['forks_count'],
                'url' => $repo['html_url'],
                'topics' => $repo['topics'] ?? [],
                'created_at' => $repo['created_at'],
                'updated_at' => $repo['updated_at'],
            ];
        }, $repositories);
    }

    /**
     * Make authenticated request to GitHub API
     */
    protected function getRequest(string $endpoint, array $params = [])
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
        ];

        if ($this->token) {
            $headers['Authorization'] = "token {$this->token}";
        }

        return Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->get($this->apiUrl . $endpoint, $params);
    }

    /**
     * Post request to GitHub API
     */
    protected function postRequest(string $endpoint, array $data = [])
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
        ];

        if ($this->token) {
            $headers['Authorization'] = "token {$this->token}";
        }

        return Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($this->apiUrl . $endpoint, $data);
    }
}
