<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;

class AssignFreePlanToUser
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        try {
            $freePlan = SubscriptionPlan::where('slug', 'free')
                ->where('is_active', true)
                ->first();

            if (!$freePlan) {
                Log::error('Free plan not found for user registration', ['user_id' => $user->id]);
                return;
            }

            $this->subscriptionService->subscribe(
                user: $user,
                plan: $freePlan,
                billingPeriod: 'lifetime',
                paymentGateway: 'free'
            );

            Log::info('Free plan assigned to user', ['user_id' => $user->id]);

        } catch (\Exception $e) {
            Log::error('Failed to assign free plan', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
