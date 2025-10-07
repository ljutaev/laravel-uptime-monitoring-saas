<?php

// app/Models/Check.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    public $timestamps = false; // Використовуємо тільки checked_at

    protected $fillable = [
        'monitor_id',
        'is_up',
        'status_code',
        'response_time',
        'ssl_valid',
        'ssl_expires_at',
        'error_message',
        'error_type',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'is_up' => 'boolean',
        'ssl_valid' => 'boolean',
    ];

    // Relationships
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('is_up', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_up', false);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('checked_at', '>=', now()->subDays($days));
    }
}
