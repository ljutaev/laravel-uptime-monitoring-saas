<?php

// app/Jobs/CheckMonitorJob.php
namespace App\Jobs;

use App\Models\Monitor;
use App\Services\MonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Спроби
    public $timeout = 60; // Таймаут

    public function __construct(
        public Monitor $monitor
    ) {}

    /**
     * Виконати Job
     */
    public function handle(MonitoringService $monitoringService): void
    {
        try {
            // Перевіряємо чи монітор активний
            if ($this->monitor->status === 'paused') {
                Log::info("Monitor {$this->monitor->id} is paused, skipping check");
                return;
            }

            // Виконуємо перевірку
            $check = $monitoringService->checkMonitor($this->monitor);

            Log::info("Monitor {$this->monitor->id} checked: " . ($check->is_up ? 'UP' : 'DOWN'));

        } catch (\Exception $e) {
            Log::error("Error checking monitor {$this->monitor->id}: {$e->getMessage()}");

            // Не викидаємо exception, щоб не блокувати чергу
            // Job вважається успішним, але помилка залогована
        }
    }

    /**
     * Обробка failed job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CheckMonitorJob failed for monitor {$this->monitor->id}: {$exception->getMessage()}");
    }
}
