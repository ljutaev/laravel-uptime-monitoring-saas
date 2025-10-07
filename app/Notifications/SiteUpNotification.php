<?php

// app/Notifications/SiteUpNotification.php
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
        $channels = [];

        if ($notifiable->email_notifications) {
            $channels[] = 'mail';
        }

        if ($notifiable->telegram_enabled && $notifiable->telegram_chat_id) {
//            $channels[] = 'telegram';
        }

        return $channels;
    }

    /**
     * Email сповіщення
     */
    public function toMail($notifiable): MailMessage
    {
        $downtime = $this->incident->getDurationFormatted();

        return (new MailMessage)
            ->success()
            ->subject("✅ Сайт відновлено: {$this->monitor->name}")
            ->greeting("Добрі новини!")
            ->line("Ваш сайт **{$this->monitor->name}** знову доступний.")
            ->line("**URL:** {$this->monitor->url}")
            ->line("**Час простою:** {$downtime}")
            ->line("**Відновлено:** " . $this->incident->resolved_at->format('d.m.Y H:i:s'))
            ->action('Переглянути статистику', url("/monitors/{$this->monitor->id}"));
    }

    /**
     * Telegram сповіщення
     */
    public function toTelegram($notifiable): array
    {
        $downtime = $this->incident->getDurationFormatted();

        $text = "✅ *Сайт відновлено*\n\n";
        $text .= "Сайт: *{$this->monitor->name}*\n";
        $text .= "URL: {$this->monitor->url}\n";
        $text .= "Час простою: {$downtime}\n";
        $text .= "Відновлено: " . $this->incident->resolved_at->format('d.m.Y H:i');

        return [
            'chat_id' => $notifiable->telegram_chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
    }
}
