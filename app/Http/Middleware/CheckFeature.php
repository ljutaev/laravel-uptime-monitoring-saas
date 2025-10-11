<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Subscription\FeatureUsageService;

class CheckFeature
{
    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasFeature($feature)) {
            return redirect()
                ->back()
                ->with('error', 'Your current plan does not include this feature.');
        }

        if (!$this->featureUsage->canUse($user, $feature)) {
            return redirect()
                ->back()
                ->with('error', 'You have reached the limit for this feature. Please upgrade your plan.');
        }

        return $next($request);
    }
}
