<?php

namespace App\Http\Middleware;

use App\Services\Subscription\FeatureUsageService;
use Closure;
use Illuminate\Http\Request;

class CheckFeatureLimit
{
    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}

    public function handle(Request $request, Closure $next, string $feature, int $amount = 1)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasActiveSubscription()) {
            return redirect()
                ->route('subscription.plans')
                ->with('error', 'You need an active subscription.');
        }

        if (!$this->featureUsage->canUse($user, $feature, $amount)) {
            $remaining = $this->featureUsage->getRemaining($user, $feature);

            return back()->with('error', "You have reached your limit for {$feature}. Remaining: {$remaining}. Please upgrade your plan.");
        }

        return $next($request);
    }
}
