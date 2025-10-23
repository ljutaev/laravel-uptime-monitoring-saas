<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringPayments extends Command
{
    protected $signature = 'subscriptions:charge';
    protected $description = 'Process recurring subscription payments';

    public function __construct(
        private PaymentGatewayManager $gatewayManager
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('gateway_subscription_id')
            ->whereDate('ends_at', '<=', now()->addDay())
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $gateway = $this->gatewayManager->gateway($subscription->payment_gateway);
                $result = $gateway->chargeRecurring($subscription);

                if ($result['success']) {
                    $this->info("✓ Charged subscription #{$subscription->id}");
                } else {
                    // ⚠️ ЯКЩО НЕ ВДАЛОСЯ СПИСАТИ
                    $this->handleFailedPayment($subscription, $result);
                }

            } catch (\Exception $e) {
                $this->error("✗ Error subscription #{$subscription->id}: {$e->getMessage()}");
                $this->handleFailedPayment($subscription, ['message' => $e->getMessage()]);
            }
        }

        return 0;
    }

    private function handleFailedPayment(Subscription $subscription, array $result)
    {
        // Відправити email користувачу @TODO: розкоментувати коли буде готово
//        $subscription->user->notify(new PaymentFailedNotification($subscription, $result));

        // Встановити grace period (якщо є)
        if ($subscription->plan->grace_period_days > 0) {
            $subscription->update([
                'ends_at' => now()->addDays($subscription->plan->grace_period_days),
            ]);

            $this->info("⏳ Grace period set for subscription #{$subscription->id}");
        } else {
            // Скасувати підписку
            $subscription->expire();
            $this->warn("⚠️ Subscription #{$subscription->id} expired");
        }
    }
}
