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
     * Зміна плану підписки
     */
    public function changePlan(
        Subscription $subscription,
        SubscriptionPlan $newPlan,
        string $billingPeriod = 'monthly',
        bool $immediate = false
    ): Subscription {
        return DB::transaction(function () use ($subscription, $newPlan, $billingPeriod, $immediate) {
            $planPrice = $newPlan->getPrice($billingPeriod);

            if ($immediate) {
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'billing_period' => $billingPeriod,
                    'price' => $planPrice ? $planPrice->getFinalPrice() : 0,
                    'currency' => $planPrice->currency ?? 'USD',
                    'ends_at' => $this->calculateEndDate($billingPeriod, now()),
                ]);
            } else {
                $subscription->update([
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'scheduled_plan_change' => [
                            'new_plan_id' => $newPlan->id,
                            'billing_period' => $billingPeriod,
                            'change_at' => $subscription->ends_at,
                        ]
                    ])
                ]);
            }

            return $subscription->fresh();
        });
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

    /**
     * Перевірка та оновлення прострочених підписок
     */
    public function checkExpiredSubscriptions(): int
    {
        $expiredCount = 0;

        Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<', now())
            ->chunk(100, function ($subscriptions) use (&$expiredCount) {
                foreach ($subscriptions as $subscription) {
                    $subscription->expire();
                    $expiredCount++;
                }
            });

        return $expiredCount;
    }

    /**
     * Отримання статистики використання
     */
    public function getSubscriptionStats(User $user): array
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return [
                'has_subscription' => false,
            ];
        }

        $plan = $subscription->plan;
        $featureUsage = app(FeatureUsageService::class);
        $usage = $featureUsage->getUsageStats($user);

        return [
            'has_subscription' => true,
            'plan' => $plan,
            'subscription' => $subscription,
            'usage' => $usage,
            'days_until_renewal' => $subscription->ends_at
                ? now()->diffInDays($subscription->ends_at)
                : null,
            'on_trial' => $subscription->onTrial(),
            'on_grace_period' => $subscription->onGracePeriod(),
        ];
    }
}
