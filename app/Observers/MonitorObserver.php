<?php

namespace App\Observers;

use App\Models\Monitor;
use App\Services\Subscription\FeatureUsageService;
use Illuminate\Support\Facades\Log;


class MonitorObserver
{
    public function __construct(
        protected FeatureUsageService $featureUsage
    ) {}
    /**
     * Handle the Monitor "created" event.
     */
    public function created(Monitor $monitor): void
    {
        //
    }

    /**
     * Handle the Monitor "updated" event.
     */
    public function updated(Monitor $monitor): void
    {
        //
    }

    /**
     * Handle the Monitor "deleted" event.
     */
    public function deleted(Monitor $monitor): void
    {
        Log:info('Monitor deleted', [
            'monitor_id' => $monitor->id,
            'user_id' => $monitor->user_id,
        ]);
        try {
            if ($monitor->user) {
                $this->featureUsage->decrease($monitor->user, 'domains', 1);
                Log::info('Monitor deleted - feature usage decreased', [
                    'monitor_id' => $monitor->id,
                    'user_id' => $monitor->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to decrease monitor usage', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Monitor "restored" event.
     */
    public function restored(Monitor $monitor): void
    {
        //
    }

    /**
     * Handle the Monitor "force deleted" event.
     */
    public function forceDeleted(Monitor $monitor): void
    {
        //
    }
}
