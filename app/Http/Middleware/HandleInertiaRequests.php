<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\Subscription\FeatureUsageService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = parent::share($request);

        if( $request->user() ) {
            $user = $request->user();
            $subscription = $user->activeSubscription;

            $shared['auth']['subscription'] = $subscription ? [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'plan_slug' => $subscription->plan->slug,
                'plan_check_interval' => $subscription->plan->getFeatureValue('check_interval'), // хвилини
                'billing_period' => $subscription->billing_period,
                'price' => $subscription->price,
                'currency' => $subscription->currency,
                'status' => $subscription->status,
                'is_active' => $subscription->isActive(),
                'on_trial' => $subscription->onTrial(),
                'ends_at' => $subscription->ends_at?->toDateTimeString(),
            ] : null;





            $featureUsage = app(FeatureUsageService::class);

            $shared['auth']['limits'] = [
                'monitors' => [
                    'remaining' => $featureUsage->getRemaining($user, 'domains'),
                    'can_add' => $featureUsage->canUse($user, 'domains', 1),
                    'used' => $featureUsage->getCurrentUsage($user, 'domains'),
                ],

            ];
        }

        return $shared;
    }
}
