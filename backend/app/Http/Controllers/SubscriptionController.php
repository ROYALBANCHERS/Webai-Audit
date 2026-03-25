<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use App\Models\Subscription;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all available subscription plans
     */
    public function plans(): JsonResponse
    {
        $plans = $this->subscriptionService->getAvailablePlans();

        return response()->json([
            'success' => true,
            'data' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
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
            }),
        ]);
    }

    /**
     * Get current user's subscription
     */
    public function current(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();
        $usage = $this->subscriptionService->getUsageStats();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => null,
                    'credits_remaining' => 0,
                    'usage' => $usage,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => [
                    'id' => $subscription->subscription->id,
                    'name' => $subscription->subscription->name,
                    'slug' => $subscription->subscription->slug,
                    'price' => $subscription->subscription->formatted_price,
                    'credits_per_month' => $subscription->subscription->credits_per_month,
                ],
                'credits_remaining' => $subscription->credits_remaining,
                'credits_reset_at' => $subscription->credits_reset_at,
                'usage' => $usage,
            ],
        ]);
    }

    /**
     * Get feature comparison across all plans
     */
    public function compare(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getFeatureComparison(),
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'plan_id' => 'required|integer|exists:subscriptions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $userSubscription = $this->subscriptionService->changeSubscription(
                $request->input('plan_id')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $userSubscription->load('subscription'),
                    'message' => 'Successfully subscribed to ' . $userSubscription->subscription->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription usage stats
     */
    public function usage(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getUsageStats(),
        ]);
    }
}
