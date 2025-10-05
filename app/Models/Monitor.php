<?php

// app/Models/Monitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Monitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'type',
        'check_interval',
        'timeout',
        'status',
        'last_checked_at',
        'last_status_code',
        'last_response_time',
        'uptime_7d',
        'uptime_30d',
        'total_incidents',
        'notifications_enabled',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'uptime_7d' => 'decimal:2',
        'uptime_30d' => 'decimal:2',
        'notifications_enabled' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checks()
    {
        return $this->hasMany(Check::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['up', 'down']);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }

    // Helper methods
    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'up' => 'green',
            'down' => 'red',
            'paused' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'up' => '●',
            'down' => '●',
            'paused' => '⏸',
            default => '●',
        };
    }
}
