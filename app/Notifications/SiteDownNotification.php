<?php

namespace App\Notifications;

use App\Models\Monitor;
use App\Models\Incident;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class SiteDownNotification extends Notification
{
    public function __construct(
        public Monitor $monitor,
        public Incident $incident
    ) {}

    /**
     * Канали сповіщень
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Email сповіщення
     */
    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending SiteDownNotification email', [
            'monitor_id' => $this->monitor->id,
            'incident_id' => $this->incident->id,
        ]);

        return (new MailMessage)
            ->error()
            ->subject("🔴 Site Down: {$this->monitor->name}")
            ->greeting("Alert!")
            ->line("Your website **{$this->monitor->name}** is currently down.")
            ->line("**URL:** {$this->monitor->url}")
            ->line("**Downtime started:** " . $this->incident->started_at->format('d.m.Y H:i:s'))
            ->line("**Status code:** " . ($this->incident->status_code ?? 'N/A'))
            ->line("**Error:** " . ($this->incident->error_message ?? 'No response'))
            ->action('View Details', url("/incidents/{$this->incident->id}"))
            ->line('We will notify you when the site is back up.');
    }
}
