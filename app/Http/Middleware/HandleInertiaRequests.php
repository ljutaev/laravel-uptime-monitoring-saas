<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\Subscription\FeatureUsageService;
use Illuminate\Support\Str;

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
        return array_merge(parent::share($request), [
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                ];
            },
            'auth' => [
                'user' => $request->user(),
                'subscription' => $request->user()?->activeSubscription ? [
                    'id' => $request->user()->activeSubscription->id,
                    'plan_name' => $request->user()->activeSubscription->plan->name,
                    'plan_slug' => $request->user()->activeSubscription->plan->slug,
                    'plan_check_interval' => $request->user()->activeSubscription->plan->getFeatureValue('check_interval'),
                    'billing_period' => $request->user()->activeSubscription->billing_period,
                    'price' => $request->user()->activeSubscription->price,
                    'currency' => $request->user()->activeSubscription->currency,
                    'status' => $request->user()->activeSubscription->status,
                    'is_active' => $request->user()->activeSubscription->isActive(),
                    'on_trial' => $request->user()->activeSubscription->onTrial(),
                    'ends_at' => $request->user()->activeSubscription->ends_at?->toDateTimeString(),
                ] : null,
                'limits' => $request->user() ? [
                    'monitors' => [
                        'remaining' => app(FeatureUsageService::class)->getRemaining($request->user(), 'domains'),
                        'can_add' => app(FeatureUsageService::class)->canUse($request->user(), 'domains', 1),
                        'used' => app(FeatureUsageService::class)->getCurrentUsage($request->user(), 'domains'),
                    ],
                ] : null,

            ],
            'breadcrumbs' => $request->route()?->getName() ? explode('.', Str::ucfirst( $request->route()?->getName())) : null,
        ]);
    }
}
