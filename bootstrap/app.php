<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\ScheduleMonitorCheck;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
         ScheduleMonitorCheck::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Перевірка моніторів кожну хвилину
        $schedule->command('monitors:check')
            ->everyMinute()
            ->withoutOverlapping() // Не запускати якщо попередня команда ще працює
            ->runInBackground(); // Запускати в фоні
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
