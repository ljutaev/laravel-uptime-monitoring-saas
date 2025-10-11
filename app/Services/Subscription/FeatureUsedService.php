<?php

namespace App\Services\Subscription;

use App\Models\FeatureUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FeatureUsageService
{
    /**
     * Використання feature (додавання доменів, API calls тощо)
     */
    public function use(User $user, string $featureSlug, int $amount = 1): bool
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            throw new \Exception('No active subscription');
        }

        $feature = $subscription->plan->features()
            ->where('slug', $featureSlug)
            ->first();

        if (!$feature) {
            throw new \Exception("Feature {$featureSlug} not found in plan");
        }

        if ($feature->isUnlimited()) {
            $this->recordUsage($user, $subscription->id, $featureSlug, $amount);
            return true;
        }

        $currentUsage = $this->getCurrentUsage($user, $featureSlug);
        $limit = $feature->getNumericValue();

        if ($currentUsage + $amount > $limit) {
            throw new \Exception("Limit exceeded for {$featureSlug}. Current: {$currentUsage}, Limit: {$limit}");
        }

        $this->recordUsage($user, $subscription->id, $featureSlug, $amount);

        return true;
    }

    /**
     * Зменшення використання
     */
    public function decrease(User $user, string $featureSlug, int $amount = 1): bool
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return false;
        }

        $period = $this->getCurrentPeriod();

        $usage = FeatureUsage::where('user_id', $user->id)
            ->where('feature_slug', $featureSlug)
            ->where('period_start', $period['start'])
            ->first();

        if ($usage) {
            $newUsage = max(0, $usage->usage - $amount);
            $usage->update(['usage' => $newUsage]);
        }

        return true;
    }

    /**
     * Перевірка чи може користувач використати feature
     */
    public function canUse(User $user, string $featureSlug, int $amount = 1): bool
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return false;
        }

        $feature = $subscription->plan->features()
            ->where('slug', $featureSlug)
            ->first();

        if (!$feature) {
            return false;
        }

        if ($feature->isUnlimited()) {
            return true;
        }

        if ($feature->isBoolean()) {
            return $feature->getBooleanValue();
        }

        $currentUsage = $this->getCurrentUsage($user, $featureSlug);
        $limit = $feature->getNumericValue();

        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Отримання поточного використання
     */
    public function getCurrentUsage(User $user, string $featureSlug): int
    {
        $period = $this->getCurrentPeriod();

        return FeatureUsage::where('user_id', $user->id)
            ->where('feature_slug', $featureSlug)
            ->where('period_start', $period['start'])
            ->sum('usage') ?? 0;
    }

    /**
     * Отримання залишку feature
     */
    public function getRemaining(User $user, string $featureSlug): ?int
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return 0;
        }

        $feature = $subscription->plan->features()
            ->where('slug', $featureSlug)
            ->first();

        if (!$feature) {
            return 0;
        }

        if ($feature->isUnlimited()) {
            return null;
        }

        $limit = $feature->getNumericValue();
        $used = $this->getCurrentUsage($user, $featureSlug);

        return max(0, $limit - $used);
    }

    /**
     * Запис використання
     */
    protected function recordUsage(
        User $user,
        int $subscriptionId,
        string $featureSlug,
        int $amount
    ): void {
        $period = $this->getCurrentPeriod();

        FeatureUsage::updateOrCreate(
            [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'feature_slug' => $featureSlug,
                'period_start' => $period['start'],
            ],
            [
                'usage' => DB::raw("usage + {$amount}"),
                'period_end' => $period['end'],
            ]
        );
    }

    /**
     * Отримання детальної статистики
     */
    public function getUsageStats(User $user): array
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return [];
        }

        $stats = [];

        foreach ($subscription->plan->features as $feature) {
            $used = $this->getCurrentUsage($user, $feature->slug);
            $limit = $feature->getNumericValue();
            $remaining = $this->getRemaining($user, $feature->slug);

            $stats[$feature->slug] = [
                'name' => $feature->name,
                'used' => $used,
                'limit' => $feature->isUnlimited() ? 'unlimited' : $limit,
                'remaining' => $feature->isUnlimited() ? 'unlimited' : $remaining,
                'percentage' => $feature->isUnlimited() ? 0 : ($limit > 0 ? round(($used / $limit) * 100, 2) : 0),
            ];
        }

        return $stats;
    }

    /**
     * Скидання використання
     */
    public function resetMonthlyUsage(): void
    {
        FeatureUsage::where('period_end', '<', now())->delete();
    }

    /**
     * Отримання поточного періоду
     */
    protected function getCurrentPeriod(): array
    {
        return [
            'start' => now()->startOfMonth(),
            'end' => now()->endOfMonth(),
        ];
    }
}
