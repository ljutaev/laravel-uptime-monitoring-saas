<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Monitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Services\Subscription\FeatureUsageService;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MonitorController extends Controller
{

    use AuthorizesRequests, ValidatesRequests;

    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monitors = auth()->user()->monitors()
            ->latest()
            ->get()
            ->map(fn($monitor) => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type,
                'status' => $monitor->status,
                'status_color' => $monitor->status_color,
                'check_interval' => $monitor->check_interval,
                'uptime_30d' => $monitor->uptime_30d,
                'last_checked_at' => $monitor->last_checked_at?->diffForHumans(),
                'created_at' => $monitor->created_at->format('d M, Y'),
            ]);

        return Inertia::render('User/Monitors/Index',[
            'monitors' => $monitors,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/Monitors/Create', [
            'minInterval' => 1,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval' => 'required|integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            if (!$this->featureUsage->canUse($user, 'domains', 1)) {
                $remaining = $this->featureUsage->getRemaining($user, 'domains');
                throw new \Exception("You’ve reached your domain limit ({$remaining} remaining). Please upgrade your plan.");
            }

            $monitor = $user->monitors()->create($validated);

            $this->featureUsage->use($user, 'domains', 1);

            DB::commit();

            return redirect()->route('monitors.show', $monitor)
                ->with('success', 'Monitor created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Monitor creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('monitors.index')
                ->with('error', $e->getMessage() ?: 'Failed to create monitor.');
        }

//        return redirect()->route('monitors.index')
//            ->with('success', 'Successfully created monitor.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        $period = request('period', 'today'); // today, 7d, 30d
        $tab = request('tab', 'overview'); // overview, checks, incidents

        // Базова інформація
        $monitorData = [
            'id' => $monitor->id,
            'name' => $monitor->name,
            'url' => $monitor->url,
            'status' => $monitor->status,
            'check_interval' => $monitor->check_interval,
            'last_checked_at' => $monitor->last_checked_at?->format('Y-m-d H:i:s'),
            'created_at' => $monitor->created_at->format('Y-m-d H:i:s'),
        ];

        // Overview даних
        $days = match($period) {
            'today' => 1,
            '7d' => 7,
            '30d' => 30,
            default => 1,
        };

        $overview = [
            'uptime' => $monitor->calculateUptime($days),
            'uptime_duration' => $this->getUptimeDuration($monitor, $days),
            'avg_response_time' => $monitor->averageResponseTime($days),
            'total_checks' => $monitor->checks()->where('checked_at', '>=', now()->subDays($days))->count(),
            'incidents_count' => $monitor->incidents()->where('started_at', '>=', now()->subDays($days))->count(),
        ];

        // Графік response time
        $chartData = $this->getResponseTimeChart($monitor, $days);

        // Recent checks (для overview)
        $recentChecks = $monitor->checks()
            ->where('checked_at', '>=', now()->subDays($days))
            ->latest('checked_at')
            ->limit(5)
            ->get()
            ->map(fn($check) => [
                'status_code' => $check->status_code,
                'response_time' => $check->response_time,
                'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
            ]);

        // SSL Info
        $latestCheck = $monitor->checks()->latest('checked_at')->first();
        $sslInfo = null;

        if ($latestCheck && $latestCheck->ssl_expires_at) {
            $daysUntilExpiry = now()->diffInDays($latestCheck->ssl_expires_at, false);
            $sslInfo = [
                'valid' => $latestCheck->ssl_valid,
                'expires_at' => $latestCheck->ssl_expires_at->format('Y-m-d'),
                'days_remaining' => $daysUntilExpiry,
                'is_expiring_soon' => $daysUntilExpiry < 30,
            ];
        }

        // Checks list (для таба checks)
        $checks = null;
        if ($tab === 'checks') {
            $checks = $monitor->checks()
                ->where('checked_at', '>=', now()->subDays($days))
                ->latest('checked_at')
                ->paginate(10)
                ->through(fn($check) => [
                    'status_code' => $check->status_code,
                    'response_time' => $check->response_time,
                    'is_up' => $check->is_up,
                    'error_message' => $check->error_message,
                    'checked_at' => $check->checked_at->format('Y-m-d H:i:s'),
                ]);
        }

        // Incidents list (для таба incidents)
        $incidents = null;
        if ($tab === 'incidents') {
            $incidents = $monitor->incidents()
                ->where('started_at', '>=', now()->subDays($days))
                ->latest('started_at')
                ->paginate(10)
                ->through(fn($incident) => [
                    'id' => $incident->id,
                    'status' => $incident->status,
                    'started_at' => $incident->started_at->format('Y-m-d H:i:s'),
                    'resolved_at' => $incident->resolved_at?->format('Y-m-d H:i:s'),
                    'duration' => $incident->getDurationFormatted(),
                    'error_message' => $incident->error_message,
                ]);
        }

        return Inertia::render('User/Monitors/Show', [
            'monitor' => $monitorData,
            'overview' => $overview,
            'chartData' => $chartData,
            'recentChecks' => $recentChecks,
            'sslInfo' => $sslInfo,
            'checks' => $checks,
            'incidents' => $incidents,
            'currentTab' => $tab,
            'currentPeriod' => $period,
        ]);
    }

    private function getUptimeDuration(Monitor $monitor, int $days): string
    {
        $totalMinutes = $days * 24 * 60;
        $uptime = $monitor->calculateUptime($days);
        $uptimeMinutes = ($uptime / 100) * $totalMinutes;

        $hours = floor($uptimeMinutes / 60);
        $minutes = $uptimeMinutes % 60;

        if ($hours > 24) {
            $days = floor($hours / 24);
            $hours = $hours % 24;
            return "{$days} д {$hours} год";
        }

        return "{$hours} год {$minutes} хв";
    }

    private function getResponseTimeChart(Monitor $monitor, int $days): array
    {
        $interval = $days === 1 ? 'HOUR' : 'DATE';

        $data = \DB::table('checks')
            ->select(
                \DB::raw("{$interval}(checked_at) as period"),
                \DB::raw('AVG(response_time) as avg_response')
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        return Inertia::render('User/Monitors/Edit', [
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type,
                'check_interval' => $monitor->check_interval,
                'notifications_enabled' => $monitor->notifications_enabled,
            ],
            'minInterval' => 1,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval' => 'required|integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
        ]);

        $monitor->update($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Successfully updated monitor.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Monitor $monitor)
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return back()->with('success', 'Deleted monitor successfully.');
    }
}
