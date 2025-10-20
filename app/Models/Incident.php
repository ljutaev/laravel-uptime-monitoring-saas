<?php

// app/Models/Incident.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'monitor_id',
        'status',
        'started_at',
        'resolved_at',
        'duration',
        'status_code',
        'error_message',
        'error_type',
        'failed_checks_count',
        'email_sent',
        'telegram_sent',
        'notifications_sent_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notifications_sent_at' => 'datetime',
        'email_sent' => 'boolean',
        'telegram_sent' => 'boolean',
    ];

    // Relationships
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    // Scopes
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Helper methods
    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function resolve(): void
    {
        $this->update([
            'resolved_at' => now(),
            'duration' => now()->diffInSeconds($this->started_at),
            'status' => 'resolved',
        ]);
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration) return '-';

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d год %d хв', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%d хв %d сек', $minutes, $seconds);
        } else {
            return sprintf('%d сек', $seconds);
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'ongoing' => 'red',
            'resolved' => 'green',
            default => 'gray',
        };
    }
}
