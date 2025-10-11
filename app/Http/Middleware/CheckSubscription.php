<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $plan = null)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasActiveSubscription()) {
            return redirect()
                ->route('subscription.plans')
                ->with('error', 'You need an active subscription to access this feature.');
        }

        if ($plan && !$user->subscribedToPlan($plan)) {
            return redirect()
                ->route('subscription.plans')
                ->with('error', "This feature requires the {$plan} plan.");
        }

        return $next($request);
    }
}
