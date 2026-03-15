<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'crawl_urls',
        'selectors',
        'crawl_frequency',
        'is_active',
        'last_crawled_at',
    ];

    protected $casts = [
        'crawl_urls' => 'array',
        'selectors' => 'array',
        'crawl_frequency' => 'integer',
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
     * Check if source is due for crawling
     */
    public function isDueForCrawling(): bool
    {
        if (!$this->last_crawled_at) {
            return true;
        }

        return $this->last_crawled_at->addSeconds($this->crawl_frequency)->isPast();
    }
}
