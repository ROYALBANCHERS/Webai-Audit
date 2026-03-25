<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'interval',
        'credits_per_month',
        'max_websites',
        'max_pages_per_audit',
        'can_analyze_competitors',
        'can_use_github_search',
        'can_export_reports',
        'is_active',
        'sort_order',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'can_analyze_competitors' => 'boolean',
        'can_use_github_search' => 'boolean',
        'can_export_reports' => 'boolean',
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getIntervalDisplayAttribute(): string
    {
        return match($this->interval) {
            'monthly' => '/month',
            'yearly' => '/year',
            default => '',
        };
    }

    public function getMaxWebsitesDisplayAttribute(): string
    {
        return $this->max_websites === -1 ? 'Unlimited' : (string) $this->max_websites;
    }

    public function getMaxPagesDisplayAttribute(): string
    {
        return $this->max_pages_per_audit === -1 ? 'Unlimited' : (string) $this->max_pages_per_audit;
    }
}
