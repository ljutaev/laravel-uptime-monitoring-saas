<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_period',
        'price',
        'currency',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'payment_gateway',
        'gateway_subscription_id',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SUSPENDED = 'suspended';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at && $this->ends_at->isPast());
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        if (!$this->ends_at) {
            return false;
        }
        $gracePeriodEnd = $this->ends_at->copy()->addDays($this->plan->grace_period_days);
        return now()->between($this->ends_at, $gracePeriodEnd);
    }

    public function activate()
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => $this->starts_at ?? now(),
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'canceled_at' => now(),
        ]);
    }

    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function suspend()
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    public function renew()
    {
        $endsAt = $this->calculateNextBillingDate();
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'ends_at' => $endsAt,
            'canceled_at' => null,
        ]);
    }

    protected function calculateNextBillingDate(): Carbon
    {
        $baseDate = $this->ends_at ?? now();

        return match ($this->billing_period) {
            'monthly' => $baseDate->addMonth(),
            'yearly' => $baseDate->addYear(),
            'lifetime' => $baseDate->addYears(100),
            default => $baseDate->addMonth(),
        };
    }
}
