<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SubscriptionPlan;
use App\Services\WayForPayService;

class CheckoutController extends Controller
{
    public function __construct(
        private WayForPayService $wayForPayService
    ) {}

    public function show(SubscriptionPlan $plan, Request $request)
    {
        $interval = $request->query('interval', 'month'); // month або year

        if (!in_array($interval, ['month', 'year'])) {
            return redirect()->route('user.plans');
        }

        $user = auth()->user();

        // Перевіряємо чи користувач вже має активну підписку
        $currentSubscription = $user->subscription;

        // Розраховуємо ціну
        $price = $interval === 'year' ? $plan->price_yearly : $plan->price_monthly;

        // Якщо Free план
        if ($price == 0) {
            return redirect()->route('user.plans')
                ->with('error', 'Free план не потребує оплати');
        }

        return Inertia::render('Checkout/Show', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => $price,
                'currency' => $plan->currency,
                'interval' => $interval,
                'features' => json_decode($plan->features),
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'currentSubscription' => $currentSubscription ? [
                'plan' => $currentSubscription->plan->name,
                'ends_at' => $currentSubscription->ends_at->format('Y-m-d'),
            ] : null,
        ]);
    }

    public function process(Plan $plan, Request $request)
    {
        $validated = $request->validate([
            'interval' => 'required|in:month,year',
        ]);

        $user = auth()->user();
        $interval = $validated['interval'];
        $price = $interval === 'year' ? $plan->price_yearly : $plan->price_monthly;

        if ($price == 0) {
            return back()->withErrors(['error' => 'Free план не потребує оплати']);
        }

        // Створюємо дані для WayForPay
        $paymentData = $this->wayForPayService->createSubscriptionPayment(
            $user,
            $plan,
            $interval
        );

        // Повертаємо форму для WayForPay
        return Inertia::render('Checkout/Payment', [
            'paymentData' => $paymentData,
        ]);
    }
}
