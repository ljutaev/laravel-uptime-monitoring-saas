<?php

namespace App\Notifications;

use App\Models\Monitor;
use App\Models\Incident;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SiteUpNotification extends Notification
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
        $downtime = $this->incident->getDurationFormatted();

        return (new MailMessage)
            ->success()
            ->subject("✅ Site Restored: {$this->monitor->name}")
            ->greeting("Good news!")
            ->line("Your website **{$this->monitor->name}** is back online.")
            ->line("**URL:** {$this->monitor->url}")
            ->line("**Downtime duration:** {$downtime}")
            ->line("**Restored at:** " . $this->incident->resolved_at->format('d.m.Y H:i:s'))
            ->action('View Statistics', url("/incidents/{$this->incident->id}"));
    }
}
