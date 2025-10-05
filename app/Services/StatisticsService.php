<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\User;
use App\Models\Check;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function getMonitorStats(Monitor $monitor, int $days = 7): array
    {
        return [
            'uptime' => $monitor->calculateUptime($days),
            'avg_response_time' => $monitor->averageResponseTime($days),
            'total_checks' => $monitor->checks()->where('checked_at', '>=', now()->subDays($days))->count(),
            'failed_checks' => $monitor->checks()->where('checked_at', '>=', now()->subDays($days))->where('is_up', false)->count(),
            'total_incidents' => $monitor->incidents()->where('started_at', '>=', now()->subDays($days))->count(),
        ];
    }

    public function getUptimeChartData(Monitor $monitor, int $days = 7): array
    {
        $interval = $days === 1 ? 'HOUR' : 'DATE';
        $format = $days === 1 ? '%H:00' : '%d.%m';

        $data = DB::table('checks')
            ->select(
                DB::raw("{$interval}(checked_at) as period"),
                DB::raw('COUNT(*) as total_checks'),
                DB::raw('SUM(CASE WHEN is_up = 1 THEN 1 ELSE 0 END) as successful_checks')
            )
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $labels = [];
        $values = [];

        $periods = $days === 1 ? 24 : $days;

        for ($i = $periods - 1; $i >= 0; $i--) {
            $date = $days === 1
                ? now()->subHours($i)
                : now()->subDays($i);

            $periodValue = $days === 1
                ? $date->format('H')
                : $date->format('Y-m-d');

            $labels[] = $date->format($days === 1 ? 'H:00' : 'd.m');

            $periodData = $data->firstWhere('period', $periodValue);

            if ($periodData && $periodData->total_checks > 0) {
                $values[] = round(($periodData->successful_checks / $periodData->total_checks) * 100, 2);
            } else {
                $values[] = null;
            }
        }

        return [
            'labels' => $labels,
            'data' => $values,
        ];
    }

    public function getResponseTimeChartData(Monitor $monitor, int $days = 7): array
    {
        $interval = $days === 1 ? 'HOUR' : 'DATE';

        $data = DB::table('checks')
            ->select(
                DB::raw("{$interval}(checked_at) as period"),
                DB::raw('AVG(response_time) as avg_response')
            )
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->where('is_up', true)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $labels = [];
        $values = [];

        $periods = $days === 1 ? 24 : $days;

        for ($i = $periods - 1; $i >= 0; $i--) {
            $date = $days === 1
                ? now()->subHours($i)
                : now()->subDays($i);

            $periodValue = $days === 1
                ? $date->format('H')
                : $date->format('Y-m-d');

            $labels[] = $date->format($days === 1 ? 'H:00' : 'd.m');

            $periodData = $data->firstWhere('period', $periodValue);
            $values[] = $periodData ? round($periodData->avg_response) : null;
        }

        return [
            'labels' => $labels,
            'data' => $values,
        ];
    }

    public function getUserDashboardStats(User $user): array
    {
        $monitors = $user->monitors;

        return [
            'total_monitors' => $monitors->count(),
            'monitors_up' => $monitors->where('status', 'up')->count(),
            'monitors_down' => $monitors->where('status', 'down')->count(),
            'monitors_paused' => $monitors->where('status', 'paused')->count(),
            'average_uptime' => $monitors->avg('uptime_30d') ?? 100,
            'average_response_time' => $monitors->avg('avg_response_time_7d') ?? 0,
            'total_incidents_today' => $user->monitors()
                ->withCount(['incidents' => function($q) {
                    $q->whereDate('started_at', today());
                }])
                ->get()
                ->sum('incidents_count'),
        ];
    }

    public function getAdminDashboardStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'total_monitors' => Monitor::count(),
            'monitors_up' => Monitor::where('status', 'up')->count(),
            'monitors_down' => Monitor::where('status', 'down')->count(),
            'total_checks_today' => Check::whereDate('checked_at', today())->count(),
            'average_uptime' => Monitor::avg('uptime_30d') ?? 100,
            'active_subscriptions' => \App\Models\Subscription::active()->count(),
            'revenue_this_month' => \App\Models\Payment::completed()
                ->whereMonth('paid_at', now()->month)
                ->sum('total_amount'),
        ];
    }
}
