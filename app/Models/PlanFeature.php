<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'slug',
        'name',
        'description',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isUnlimited(): bool
    {
        return strtolower($this->value) === 'unlimited';
    }

    public function isBoolean(): bool
    {
        return in_array(strtolower($this->value), ['true', 'false', '1', '0']);
    }

    public function getNumericValue(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }
        return is_numeric($this->value) ? (int) $this->value : null;
    }

    public function getBooleanValue(): bool
    {
        return in_array(strtolower($this->value), ['true', '1']);
    }
}
