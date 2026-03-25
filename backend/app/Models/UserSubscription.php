<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'session_id',
        'status',
        'credits_remaining',
        'credits_reset_at',
        'starts_at',
        'ends_at',
        'auto_renew',
    ];

    protected $casts = [
        'credits_remaining' => 'integer',
        'credits_reset_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function hasCredits(int $required = 1): bool
    {
        return $this->credits_remaining >= $required;
    }

    public function deductCredits(int $amount): bool
    {
        if (!$this->hasCredits($amount)) {
            return false;
        }

        $this->decrement('credits_remaining', $amount);
        return true;
    }

    public function resetCredits(): void
    {
        $this->update([
            'credits_remaining' => $this->subscription->credits_per_month,
            'credits_reset_at' => now()->addMonth(),
        ]);
    }

    public function checkAndResetCreditsIfNeeded(): void
    {
        if ($this->credits_reset_at && $this->credits_reset_at->isPast()) {
            $this->resetCredits();
        }
    }

    public function canAnalyzeCompetitors(): bool
    {
        return $this->subscription?->can_analyze_competitors ?? false;
    }

    public function canUseGithubSearch(): bool
    {
        return $this->subscription?->can_use_github_search ?? false;
    }

    public function canExportReports(): bool
    {
        return $this->subscription?->can_export_reports ?? false;
    }

    public function getMaxPagesPerAudit(): int
    {
        return $this->subscription?->max_pages_per_audit ?? 5;
    }

    public function getMaxWebsites(): int
    {
        return $this->subscription?->max_websites ?? 1;
    }
}
