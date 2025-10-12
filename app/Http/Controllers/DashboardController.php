<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\Subscription\FeatureUsageService;

class DashboardController extends Controller
{
    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        $subscriptionData = null;
        if ($subscription) {

            $subscriptionData = [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'description' => $subscription->plan->description,
                ],
                'billing_period' => $subscription->billing_period,
                'price' => $subscription->price,
                'currency' => $subscription->currency,
                'trial_ends_at' => $subscription->trial_ends_at?->toDateTimeString(),
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'ends_at' => $subscription->ends_at?->toDateTimeString(),
                'on_trial' => $subscription->onTrial(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'is_active' => $subscription->isActive(),
            ];
        }

        // Лічильники моніторів
        $monitorCounts = $user->monitors()
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up")
            ->selectRaw("SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as down")
            ->selectRaw("SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused")
            ->first();

        // Перевірки за 24 години (для карти “Checks”)
        $checksCount24h = \DB::table('checks as c')
            ->join('monitors as m', 'm.id', '=', 'c.monitor_id')
            ->where('m.user_id', $user->id)
            ->where('c.checked_at', '>=', now()->subDay())
            ->count();

        // Активні інциденти (для карти “Incidents”)
        $ongoingIncidents = \DB::table('incidents as i')
            ->join('monitors as m', 'm.id', '=', 'i.monitor_id')
            ->where('m.user_id', $user->id)
            ->where('i.status', 'ongoing')
            ->count();

        // Останні перевірки (таблиця “Recent Checks”)
        $recentChecks = \DB::table('checks as c')
            ->join('monitors as m', 'm.id', '=', 'c.monitor_id')
            ->where('m.user_id', $user->id)
            ->orderByDesc('c.checked_at')
            ->limit(10)
            ->get([
                'c.status_code',
                'c.response_time',
                'c.is_up',
                'c.checked_at',
                'm.name as monitor_name',
                'm.id as monitor_id',
            ])
            ->map(fn($r) => [
                'status_code'   => $r->status_code,
                'response_time' => (int) $r->response_time,
                'is_up'         => (bool) $r->is_up,
                'checked_at'    => \Illuminate\Support\Carbon::parse($r->checked_at)->diffForHumans(),
                'monitor'       => ['id' => $r->monitor_id, 'name' => $r->monitor_name],
            ]);

        // Останні інциденти (таблиця “Incidents”)
        $recentIncidents = \DB::table('incidents as i')
            ->join('monitors as m', 'm.id', '=', 'i.monitor_id')
            ->where('m.user_id', $user->id)
            ->orderByDesc('i.started_at')
            ->limit(10)
            ->get([
                'i.id', 'i.status', 'i.started_at', 'i.resolved_at',
                'i.duration', 'i.error_message', 'm.name as monitor_name', 'm.id as monitor_id'
            ])
            ->map(function ($r) {
                $dur = (int) $r->duration;
                $durationFmt = '-';
                if ($dur) {
                    $h = intdiv($dur, 3600); $m = intdiv($dur % 3600, 60); $s = $dur % 60;
                    $durationFmt = $h > 0 ? sprintf('%d год %d хв', $h, $m) : ($m > 0 ? sprintf('%d хв %d сек', $m, $s) : sprintf('%d сек', $s));
                }
                return [
                    'id'          => $r->id,
                    'status'      => $r->status, // ongoing|resolved
                    'duration'    => $durationFmt,
                    'error'       => $r->error_message,
                    'started_at'  => optional($r->started_at)->format('Y-m-d H:i:s'),
                    'resolved_at' => optional($r->resolved_at)->format('Y-m-d H:i:s'),
                    'monitor'     => ['id' => $r->monitor_id, 'name' => $r->monitor_name],
                ];
            });

        return Inertia::render('Dashboard', [
            'subscription' => $subscriptionData,
            'usage' => $subscription ? $this->featureUsage->getUsageStats($user) : [],
            'stats' => [
                'monitors'          => [
                    'total'  => (int) $monitorCounts->total,
                    'up'     => (int) $monitorCounts->up,
                    'down'   => (int) $monitorCounts->down,
                    'paused' => (int) $monitorCounts->paused,
                ],
                'checks_24h'        => (int) $checksCount24h,
                'ongoing_incidents' => (int) $ongoingIncidents,
            ],
            'recentChecks'    => $recentChecks,
            'recentIncidents' => $recentIncidents,
        ]);
    }
}
