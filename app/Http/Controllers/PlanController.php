<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activePlanId = $user?->activeSubscription?->plan_id;

        $plans = SubscriptionPlan::with(['features', 'prices'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function ($plan) use ($activePlanId) {
                $monthly = $plan->getPrice('monthly');
                $yearly = $plan->getPrice('yearly');

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'monthly_price' => $monthly?->getFinalPrice() ?? 0,
                    'yearly_price' => $yearly?->getFinalPrice() ?? 0,
                    'currency' => $monthly?->currency ?? $yearly?->currency ?? 'USD',
                    'features' => $plan->features->map(fn($f) => [
                        'label' => $f->name,
                        'available' => !in_array(strtolower($f->value), ['false', '0', 'no']),
                        'value' => $f->value,
                    ]),
                    'active' => $plan->id === $activePlanId,
                ];
            });

        return Inertia::render('User/Plans', [
            'plans' => $plans,
        ]);
    }
}
