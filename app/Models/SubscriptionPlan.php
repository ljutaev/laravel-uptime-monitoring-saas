<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'trial_period_days',
        'grace_period_days',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_period_days' => 'integer',
        'grace_period_days' => 'integer',
        'metadata' => 'array',
    ];

    public function features()
    {
        return $this->hasMany(PlanFeature::class, 'plan_id')->orderBy('sort_order');
    }

    public function prices()
    {
        return $this->hasMany(PlanPrice::class, 'plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Отримати ціну для конкретного періоду
     */
    public function getPrice(string $billingPeriod = 'monthly', string $currency = 'USD'): ?PlanPrice
    {
        return $this->prices()
            ->where('billing_period', $billingPeriod)
            ->where('currency', $currency)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Отримати всі доступні ціни
     */
    public function getAvailablePrices(string $currency = 'USD')
    {
        return $this->prices()
            ->where('currency', $currency)
            ->where('is_active', true)
            ->get()
            ->keyBy('billing_period');
    }

    /**
     * Чи є безкоштовним
     */
    public function isFree(): bool
    {
        return $this->prices()->where('price', '>', 0)->count() === 0;
    }

    public function hasFeature(string $featureSlug): bool
    {
        return $this->features()->where('slug', $featureSlug)->exists();
    }

    public function getFeatureValue(string $featureSlug)
    {
        $feature = $this->features()->where('slug', $featureSlug)->first();
        return $feature ? $feature->value : null;
    }
}
