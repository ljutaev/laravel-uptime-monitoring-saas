<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id',
        'billing_period',
        'price',
        'currency',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Розрахунок ціни після знижки
     */
    public function getFinalPrice(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - ($this->discount_percentage / 100));
        }

        return (float) $this->price;
    }

    /**
     * Розрахунок економії
     */
    public function getSavingsAmount(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price - $this->getFinalPrice();
        }

        return 0;
    }

    /**
     * Чи є знижка
     */
    public function hasDiscount(): bool
    {
        return $this->discount_percentage > 0;
    }

    /**
     * Розрахунок економії за рік (для місячних планів)
     */
    public function getYearlySavings(): float
    {
        if ($this->billing_period === 'monthly') {
            $monthlyTotal = $this->getFinalPrice() * 12;
            $yearlyPrice = $this->plan->getPrice('yearly', $this->currency);

            if ($yearlyPrice) {
                return $monthlyTotal - $yearlyPrice->getFinalPrice();
            }
        }

        return 0;
    }
}
