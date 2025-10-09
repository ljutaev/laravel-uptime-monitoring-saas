<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $interval = $request->query('interval', 'month'); // month або year

        if (!in_array($interval, ['month', 'year'])) {
            return redirect()->route('plans.index');
        }

        $user = auth()->user();

        $plan = new \stdClass();

        $plan->id = 1;
        $plan->name = 'Pro';
        $plan->slug = 'pro';
        $plan->description = 'Professional plan';
        $plan->price_monthly = 15.00;
        $plan->price_yearly = 150.00;
        $plan->currency = 'USD';
        $plan->features = json_encode([
            'Up to 10 monitors',
            '5 minutes check interval',
            'Email and SMS alerts',
            'SSL certificate monitoring',
        ]);

        // Розраховуємо ціну
        $price = $interval === 'year' ? $plan->price_yearly : $plan->price_monthly;

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
            'currentSubscription' => null,
        ]);
    }
}
