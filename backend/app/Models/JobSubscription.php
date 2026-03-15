<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'keywords',
        'departments',
        'qualifications',
        'locations',
        'notification_methods',
        'is_active',
        'last_notified_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'departments' => 'array',
        'qualifications' => 'array',
        'locations' => 'array',
        'notification_methods' => 'array',
        'is_active' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all job matches for this subscription
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class, 'user_id', 'user_id');
    }

    /**
     * Scope to filter active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Add keyword to subscription
     */
    public function addKeyword(string $keyword): void
    {
        $keywords = $this->keywords ?? [];
        if (!in_array($keyword, $keywords)) {
            $keywords[] = $keyword;
            $this->update(['keywords' => $keywords]);
        }
    }

    /**
     * Remove keyword from subscription
     */
    public function removeKeyword(string $keyword): void
    {
        $keywords = $this->keywords ?? [];
        $keywords = array_filter($keywords, fn($k) => $k !== $keyword);
        $this->update(['keywords' => array_values($keywords)]);
    }
}
