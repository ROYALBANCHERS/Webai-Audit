<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'base_url',
        'crawl_urls',
        'selectors',
        'crawl_frequency',
        'is_active',
        'last_crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'crawl_urls' => 'array',
        'selectors' => 'array',
        'is_active' => 'boolean',
        'last_crawled_at' => 'datetime',
    ];

    /**
     * Scope to filter active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if source needs crawling
     */
    public function needsCrawling(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_crawled_at) {
            return true;
        }

        return $this->last_crawled_at->addSeconds($this->crawl_frequency)->isPast();
    }

    /**
     * Get government jobs from this source
     */
    public function govJobs()
    {
        return $this->hasMany(GovJob::class, 'source_website', 'name');
    }
}
