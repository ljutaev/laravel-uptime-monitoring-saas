<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payment\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Створення нової підписки
     */
    public function subscribe(
        User $user,
        SubscriptionPlan $plan,
        string $billingPeriod = 'monthly', // НОВИЙ ПАРАМЕТР
        string $paymentGateway = 'wayforpay',
        array $paymentData = []
    ): array {
        return DB::transaction(function () use ($user, $plan, $billingPeriod, $paymentGateway, $paymentData) {
            // Отримуємо ціну для вибраного періоду
            $planPrice = $plan->getPrice($billingPeriod, $paymentData['currency'] ?? 'USD');

            if (!$planPrice && !$plan->isFree()) {
                throw new \Exception("Price not found for {$billingPeriod} billing period");
            }

            // Скасовуємо стару активну підписку
            $activeSubscription = $user->activeSubscription;
            if ($activeSubscription) {
                $activeSubscription->cancel();
            }

            // Створюємо нову підписку
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_period' => $billingPeriod,
                'price' => $planPrice ? $planPrice->getFinalPrice() : 0,
                'currency' => $planPrice->currency ?? 'USD',
                'status' => Subscription::STATUS_PENDING,
                'payment_gateway' => $paymentGateway,
                'trial_ends_at' => $plan->trial_period_days > 0
                    ? now()->addDays($plan->trial_period_days)
                    : null,
            ]);

            // Якщо план безкоштовний
            if ($plan->isFree() || $subscription->price <= 0) {
                $subscription->activate();
                $subscription->update([
                    'starts_at' => now(),
                    'ends_at' => $this->calculateEndDate($billingPeriod),
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription,
                    'requires_payment' => false,
                ];
            }

            // Створюємо платіж через gateway
            $gateway = $this->gatewayManager->gateway($paymentGateway);
            $paymentResult = $gateway->createPayment($subscription, $paymentData);

            return array_merge($paymentResult, [
                'subscription' => $subscription,
                'requires_payment' => true,
            ]);
        });
    }

    /**
     * Скасування підписки
     */
    public function cancel(Subscription $subscription, bool $immediately = false): bool
    {
        if ($immediately) {
            $subscription->expire();
        } else {
            $subscription->cancel();
        }

        if ($subscription->payment_gateway) {
            $gateway = $this->gatewayManager->gateway($subscription->payment_gateway);
            $gateway->cancelSubscription($subscription);
        }

        return true;
    }

    /**
     * Поновлення підписки
     */
    public function renew(Subscription $subscription): bool
    {
        if (!$subscription->isCanceled() && !$subscription->isExpired()) {
            return false;
        }

        $subscription->renew();
        return true;
    }

    /**
     * Розрахунок дати закінчення
     */
    protected function calculateEndDate(string $billingPeriod, ?Carbon $startDate = null): ?Carbon
    {
        $startDate = $startDate ?? now();

        return match ($billingPeriod) {
            'monthly' => $startDate->copy()->addMonth(),
            'yearly' => $startDate->copy()->addYear(),
            'lifetime' => null,
            default => $startDate->copy()->addMonth(),
        };
    }
}
