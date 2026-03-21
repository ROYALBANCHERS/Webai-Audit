<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicCrawlSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'category',
        'scraping_strategy',
        'agentql_query',
        'selectors',
        'auto_update',
        'crawl_frequency',
        'is_active',
        'status',
        'total_jobs_found',
        'success_count',
        'failure_count',
        'last_crawled_at',
        'next_crawl_at',
        'is_featured',
        'display_order',
        'last_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selectors' => 'array',
        'auto_update' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'last_crawled_at' => 'datetime',
        'next_crawl_at' => 'datetime',
    ];

    /**
     * Get the jobs for this source.
     */
    public function govJobs(): HasMany
    {
        return $this->hasMany(GovJob::class, 'public_source_id');
    }

    /**
     * Get the scraping logs for this source.
     */
    public function scrapingLogs(): HasMany
    {
        return $this->hasMany(ScrapingLog::class, 'source_id');
    }

    /**
     * Scope to only include active sources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', '!=', 'paused');
    }

    /**
     * Scope to only include featured sources.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get sources due for crawling.
     */
    public function scopeDueForCrawling($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_crawl_at')
              ->orWhere('next_crawl_at', '<=', now());
        });
    }

    /**
     * Scope to get sources by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->success_count / $total) * 100, 2);
    }

    /**
     * Check if source is due for crawling.
     */
    public function isDueForCrawling(): bool
    {
        if (!$this->is_active || $this->status === 'paused') {
            return false;
        }

        if ($this->next_crawl_at === null) {
            return true;
        }

        return $this->next_crawl_at->isPast();
    }

    /**
     * Mark as currently being crawled.
     */
    public function markAsCrawling(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Mark as successfully crawled.
     */
    public function markAsSuccess(int $jobsFound = 0): void
    {
        $this->update([
            'status' => 'active',
            'last_crawled_at' => now(),
            'next_crawl_at' => now()->addSeconds($this->crawl_frequency),
            'total_jobs_found' => $this->total_jobs_found + $jobsFound,
            'success_count' => $this->success_count + 1,
            'last_error' => null,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_crawled_at' => now(),
            'next_crawl_at' => now()->addSeconds($this->crawl_frequency * 2), // Backoff
            'failure_count' => $this->failure_count + 1,
            'last_error' => $error,
        ]);
    }

    /**
     * Pause the source.
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Resume the source.
     */
    public function resume(): void
    {
        $this->update([
            'status' => 'pending',
            'next_crawl_at' => now(),
        ]);
    }
}
