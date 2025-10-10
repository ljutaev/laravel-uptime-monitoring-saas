<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'price',
        'currency',
        'billing_period',
        'trial_period_days',
        'grace_period_days',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'trial_period_days' => 'integer',
        'grace_period_days' => 'integer',
        'metadata' => 'array',
    ];

    public function features()
    {
        return $this->hasMany(PlanFeature::class, 'plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
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

    public function isFree(): bool
    {
        return $this->price <= 0;
    }
}
