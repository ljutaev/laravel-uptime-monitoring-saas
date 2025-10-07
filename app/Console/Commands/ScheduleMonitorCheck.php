<?php

// app/Console/Commands/ScheduleMonitorChecks.php
namespace App\Console\Commands;

use App\Models\Monitor;
use App\Jobs\CheckMonitorJob;
use Illuminate\Console\Command;

class ScheduleMonitorCheck extends Command
{
    protected $signature = 'monitors:check';
    protected $description = 'Schedule checks for all active monitors';

    public function handle(): void
    {
        // Знаходимо всі активні монітори
        $monitors = Monitor::where('status', '!=', 'paused')
//            ->whereHas('user', function ($query) {
//                // Тільки користувачі з активною підпискою
//                $query->whereHas('subscription', function ($q) {
//                    $q->where('status', 'active')
//                        ->where('ends_at', '>', now());
//                });
//            })
            ->get();

        $dispatched = 0;

        foreach ($monitors as $monitor) {
            // Перевіряємо чи прийшов час для наступної перевірки
            if ($this->shouldCheck($monitor)) {
                CheckMonitorJob::dispatch($monitor);
                $dispatched++;
            }
        }

        $this->info("✓ Dispatched {$dispatched} monitor checks");
    }

    /**
     * Перевірити чи потрібно чекати монітор
     */
    private function shouldCheck(Monitor $monitor): bool
    {
        // Якщо ніколи не перевірявся - треба перевірити
        if (!$monitor->last_checked_at) {
            return true;
        }

        // Перевіряємо чи минув інтервал
        $nextCheckTime = $monitor->last_checked_at->addMinutes($monitor->check_interval);

        return now()->greaterThanOrEqualTo($nextCheckTime);
    }
}
