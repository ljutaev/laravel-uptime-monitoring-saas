<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Показати всі доступні плани
     */
    public function plans(Request $request)
    {
        $plans = SubscriptionPlan::with(['features', 'prices'])
            ->where('is_active', true)
            ->get()
            ->map(function ($plan) {
                $monthlyPrice = $plan->getPrice('monthly', 'USD');
                $yearlyPrice = $plan->getPrice('yearly', 'USD');

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'trial_period_days' => $plan->trial_period_days,
                    'features' => $plan->features->map(fn($f) => [
                        'name' => $f->name,
                        'value' => $f->value,
                        'slug' => $f->slug,
                    ]),
                    'prices' => [
                        'monthly' => $monthlyPrice ? [
                            'price' => $monthlyPrice->price,
                            'final_price' => $monthlyPrice->getFinalPrice(),
                            'has_discount' => $monthlyPrice->hasDiscount(),
                            'discount_percentage' => $monthlyPrice->discount_percentage,
                        ] : null,
                        'yearly' => $yearlyPrice ? [
                            'price' => $yearlyPrice->price,
                            'final_price' => $yearlyPrice->getFinalPrice(),
                            'has_discount' => $yearlyPrice->hasDiscount(),
                            'discount_percentage' => $yearlyPrice->discount_percentage,
                            'savings' => $yearlyPrice->getSavingsAmount(),
                        ] : null,
                    ],
                ];
            });

        $currentSubscription = $request->user()?->activeSubscription;

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'current_plan_slug' => $currentSubscription?->plan->slug,
        ]);
    }

    /**
     * Підписатися на план
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_period' => 'required|in:monthly,yearly',
            'phone' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $user = $request->user();

        try {
            $result = $this->subscriptionService->subscribe(
                user: $user,
                plan: $plan,
                billingPeriod: $validated['billing_period'],
                paymentGateway: 'wayforpay',
                paymentData: [
                    'phone' => $validated['phone'] ?? '',
                    'currency' => 'USD',
                ]
            );

            if ($result['requires_payment']) {
                // Повертаємо дані для форми оплати
                return Inertia::render('Subscription/Payment', [
                    'payment_data' => $result['form_data'],
                    'action_url' => $result['action_url'],
                    'subscription' => $result['subscription'],
                ]);
            }

            return redirect()
                ->route('dashboard')
                ->with('success', 'Successfully subscribed to ' . $plan->name);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Return URL після оплати
     */
    public function paymentReturn(Request $request)
    {
        return Inertia::render('Subscription/PaymentReturn', [
            'status' => $request->input('transactionStatus'),
            'message' => 'Processing your payment...',
        ]);
    }

    /**
     * Скасування підписки
     */
    public function cancel(Request $request)
    {
        $subscription = $request->user()->activeSubscription;

        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $this->subscriptionService->cancel($subscription, immediately: false);

            return back()->with('success', 'Subscription will be canceled at the end of the billing period.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Поновлення підписки
     */
    public function resume(Request $request)
    {
        $subscription = $request->user()->subscriptions()
            ->where('status', 'canceled')
            ->latest()
            ->first();

        if (!$subscription) {
            return back()->with('error', 'No canceled subscription found.');
        }

        try {
            $this->subscriptionService->renew($subscription);

            return back()->with('success', 'Subscription resumed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
