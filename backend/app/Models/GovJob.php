<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'department',
        'description',
        'qualification',
        'vacancy_count',
        'last_date_to_apply',
        'salary',
        'location',
        'source_url',
        'source_website',
        'category',
        'is_active',
        'crawled_at',
    ];

    protected $casts = [
        'last_date_to_apply' => 'date',
        'crawled_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get all matches for this job
     */
    public function matches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    /**
     * Scope to filter active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get jobs expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('last_date_to_apply', [
            now(),
            now()->addDays($days)
        ]);
    }

    /**
     * Scope to get jobs by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if job is expiring soon
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->last_date_to_apply &&
               $this->last_date_to_apply->lessThanOrEqualTo(now()->addDays($days)) &&
               $this->last_date_to_apply->greaterThanOrEqualTo(now());
    }

    /**
     * Get days remaining to apply
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->last_date_to_apply) {
            return null;
        }

        return now()->diffInDays($this->last_date_to_apply, false);
    }

    /**
     * Get formatted salary
     */
    public function getFormattedSalaryAttribute(): string
    {
        if (!$this->salary) {
            return 'Not specified';
        }

        return $this->salary;
    }
}
