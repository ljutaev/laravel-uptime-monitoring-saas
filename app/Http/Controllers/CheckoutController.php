<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PaymentGatewayManager $gatewayManager
    ) {}

    public function show(SubscriptionPlan $plan, Request $request)
    {
        $interval = $request->query('interval', 'month'); // month | year
        if (!in_array($interval, ['month', 'year'])) {
            return redirect()->route('user.plans');
        }

        $billingPeriod = $interval === 'year' ? 'yearly' : 'monthly';
        $price = optional($plan->getPrice($billingPeriod))->getFinalPrice() ?? 0;
        $currency = optional($plan->getPrice($billingPeriod))->currency ?? 'USD';

        if ($price <= 0) {
            return redirect()->route('user.plans')->with('error', 'Free план не потребує оплати');
        }

        $user = $request->user();

        return Inertia::render('Checkout/Show', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => $price,
                'currency' => $currency,
                'interval' => $interval, // month|year
                'features' => $plan->features->map(fn($f) => $f->only(['name','value'])),
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'paymentMethods' => $this->gatewayManager->getAvailableGateways(), // ['wayforpay']
        ]);
    }

    public function process(SubscriptionPlan $plan, Request $request)
    {
        $validated = $request->validate([
            'interval' => 'required|in:month,year',
            'payment_method' => 'required|string', // e.g. 'wayforpay'
        ]);

        $billingPeriod = $validated['interval'] === 'year' ? 'yearly' : 'monthly';
        $paymentMethod = $validated['payment_method'];

        $result = $this->subscriptionService->subscribe(
            $request->user(),
            $plan,
            $billingPeriod,
            $paymentMethod, // 'wayforpay'
            ['currency' => optional($plan->getPrice($billingPeriod))->currency ?? 'USD']
        );

        if (!$result['requires_payment']) {
            return redirect()->route('checkout.success');
        }

        // Відправляємо на сторінку автопоста (генерично під будь-який gateway)
        return Inertia::render('Checkout/Payment', [
            'actionUrl' => $result['action_url'],
            'formData'  => $result['form_data'],
        ]);
    }

    public function success()
    {
        return Inertia::render('Checkout/Success');
    }
}
