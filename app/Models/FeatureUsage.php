<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureUsage extends Model
{
    protected $table = 'feature_usage';
    protected $fillable = [
        'user_id',
        'subscription_id',
        'feature_slug',
        'usage',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'usage' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
