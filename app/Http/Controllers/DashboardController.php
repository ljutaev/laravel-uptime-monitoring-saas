<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\Subscription\FeatureUsageService;

class DashboardController extends Controller
{
    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        $subscriptionData = null;
        $usageStats = [];

        if ($subscription) {
            $usageStats = $this->featureUsage->getUsageStats($user);

            $subscriptionData = [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'description' => $subscription->plan->description,
                ],
                'billing_period' => $subscription->billing_period,
                'price' => $subscription->price,
                'currency' => $subscription->currency,
                'trial_ends_at' => $subscription->trial_ends_at?->toDateTimeString(),
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'ends_at' => $subscription->ends_at?->toDateTimeString(),
                'on_trial' => $subscription->onTrial(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'is_active' => $subscription->isActive(),
            ];
        }

        return Inertia::render('Dashboard', [
            'subscription' => $subscriptionData,
            'usage' => $usageStats,
        ]);
    }
}
