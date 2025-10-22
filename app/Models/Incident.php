<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function resolve(): void
    {
        $resolvedAt = now();

        // Обчислюємо тривалість (завжди позитивне значення)
        $duration = abs($this->started_at->diffInSeconds($resolvedAt));

        $this->update([
            'status' => 'resolved',
            'resolved_at' => $resolvedAt,
            'duration' => $duration,
        ]);
    }

    public function getDurationFormatted(): string
    {
        // Якщо incident ще не вирішено
        if (!$this->duration && $this->status === 'ongoing') {
            $currentDuration = abs(now()->diffInSeconds($this->started_at));
            return $this->formatDuration($currentDuration) . ' (ongoing)';
        }

        // Якщо duration не встановлено
        if (!$this->duration) {
            return 'N/A';
        }

        return $this->formatDuration($this->duration);
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            if ($hours >= 24) {
                $days = floor($hours / 24);
                $hrs = $hours % 24;
                return "{$days}d {$hrs}h {$mins}m";
            }

            return "{$hours}h {$mins}m";
        }

        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }

        return "{$secs}s";
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
