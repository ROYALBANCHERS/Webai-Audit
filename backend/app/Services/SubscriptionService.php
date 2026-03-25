<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    protected ?string $sessionId = null;

    public function __construct()
    {
        $this->sessionId = $this->getOrCreateSessionId();
    }

    /**
     * Get or create session ID for guest users
     */
    protected function getOrCreateSessionId(): string
    {
        if (session()->has('guest_session_id')) {
            return session('guest_session_id');
        }

        $sessionId = 'guest_' . Str::random(32);
        session(['guest_session_id' => $sessionId]);

        // Ensure guest has a free subscription
        $this->ensureGuestSubscription($sessionId);

        return $sessionId;
    }

    /**
     * Ensure guest has a free subscription
     */
    protected function ensureGuestSubscription(string $sessionId): void
    {
        $existing = UserSubscription::where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();

        if (!$existing) {
            $freePlan = Subscription::where('slug', 'free')->first();

            if ($freePlan) {
                UserSubscription::create([
                    'session_id' => $sessionId,
                    'subscription_id' => $freePlan->id,
                    'status' => 'active',
                    'credits_remaining' => $freePlan->credits_per_month,
                    'credits_reset_at' => now()->addMonth(),
                    'starts_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get current user's subscription
     */
    public function getCurrentSubscription(): ?UserSubscription
    {
        $userId = auth()->id();

        $subscription = UserSubscription::query()
            ->when($userId, function ($q) use ($userId) {
                return $q->where('user_id', $userId);
            }, function ($q) {
                return $q->where('session_id', $this->sessionId);
            })
            ->active()
            ->with('subscription')
            ->first();

        if ($subscription) {
            $subscription->checkAndResetCreditsIfNeeded();
        }

        return $subscription;
    }

    /**
     * Get current user's subscription plan
     */
    public function getCurrentPlan(): ?Subscription
    {
        return $this->getCurrentSubscription()?->subscription;
    }

    /**
     * Check if user can perform an action
     */
    public function canPerformAction(string $action, int $credits = 1): bool
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription) {
            return false;
        }

        if (!$subscription->hasCredits($credits)) {
            return false;
        }

        return match($action) {
            'competitor_analysis' => $subscription->canAnalyzeCompetitors(),
            'github_search' => $subscription->canUseGithubSearch(),
            'export_reports' => $subscription->canExportReports(),
            default => true,
        };
    }

    /**
     * Deduct credits for an action
     */
    public function deductCredits(int $amount = 1): bool
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription || !$subscription->hasCredits($amount)) {
            return false;
        }

        return $subscription->deductCredits($amount);
    }

    /**
     * Get remaining credits
     */
    public function getRemainingCredits(): int
    {
        return $this->getCurrentSubscription()?->credits_remaining ?? 0;
    }

    /**
     * Get all available subscription plans
     */
    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::active()->ordered()->get();
    }

    /**
     * Get plan by slug
     */
    public function getPlanBySlug(string $slug): ?Subscription
    {
        return Subscription::where('slug', $slug)->first();
    }

    /**
     * Upgrade/downgrade subscription
     */
    public function changeSubscription(int $subscriptionId): UserSubscription
    {
        $userId = auth()->id();
        $newPlan = Subscription::findOrFail($subscriptionId);

        // Deactivate current subscription
        $current = $this->getCurrentSubscription();
        if ($current) {
            $current->update(['status' => 'cancelled']);
        }

        // Create new subscription
        return UserSubscription::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $this->sessionId,
            'subscription_id' => $newPlan->id,
            'status' => 'active',
            'credits_remaining' => $newPlan->credits_per_month,
            'credits_reset_at' => now()->addMonth(),
            'starts_at' => now(),
            'auto_renew' => false,
        ]);
    }

    /**
     * Get subscription usage stats
     */
    public function getUsageStats(): array
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription) {
            return [
                'credits_used' => 0,
                'credits_remaining' => 0,
                'credits_total' => 0,
                'resets_at' => null,
            ];
        }

        $plan = $subscription->subscription;
        $creditsUsed = $plan->credits_per_month - $subscription->credits_remaining;

        return [
            'credits_used' => max(0, $creditsUsed),
            'credits_remaining' => $subscription->credits_remaining,
            'credits_total' => $plan->credits_per_month,
            'resets_at' => $subscription->credits_reset_at,
            'max_websites' => $subscription->getMaxWebsites(),
            'max_pages' => $subscription->getMaxPagesPerAudit(),
            'can_analyze_competitors' => $subscription->canAnalyzeCompetitors(),
            'can_use_github' => $subscription->canUseGithubSearch(),
            'can_export' => $subscription->canExportReports(),
        ];
    }

    /**
     * Get feature comparison for all plans
     */
    public function getFeatureComparison(): array
    {
        $plans = $this->getAvailablePlans();

        return $plans->map(function ($plan) {
            return [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'price' => $plan->formatted_price,
                'interval' => $plan->interval,
                'credits' => $plan->credits_per_month,
                'max_websites' => $plan->max_websites_display,
                'max_pages' => $plan->max_pages_display,
                'features' => $plan->features ?? [],
                'competitors' => $plan->can_analyze_competitors,
                'github' => $plan->can_use_github_search,
                'export' => $plan->can_export_reports,
            ];
        })->toArray();
    }
}
