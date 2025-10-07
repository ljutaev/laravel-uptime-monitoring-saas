<?php

// app/Notifications/SiteDownNotification.php
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

        Log::info('Preparing SiteDownNotification email', [
            'user_id' => $notifiable->id,
            'monitor_id' => $this->monitor->id,
            'incident_id' => $this->incident->id,
        ]);

        return (new MailMessage)
            ->error()
            ->subject("🔴 Сайт недоступний: {$this->monitor->name}")
            ->greeting("Привіт, {$notifiable->name}!")
            ->line("Ваш сайт **{$this->monitor->name}** недоступний.")
            ->line("**URL:** {$this->monitor->url}")
            ->line("**Час падіння:** " . $this->incident->started_at->format('d.m.Y H:i:s'))
            ->line("**Статус код:** " . ($this->incident->status_code ?? 'N/A'))
            ->line("**Помилка:** " . ($this->incident->error_message ?? 'Немає відповіді'))
            ->action('Переглянути деталі', url("/monitors/{$this->monitor->id}"))
            ->line('Ми повідомимо вас, коли сайт відновиться.');
    }

    /**
     * Telegram сповіщення
     */
    public function toTelegram($notifiable): array
    {
        $text = "🔴 *Сайт недоступний*\n\n";
        $text .= "Сайт: *{$this->monitor->name}*\n";
        $text .= "URL: {$this->monitor->url}\n";
        $text .= "Час: " . $this->incident->started_at->format('d.m.Y H:i') . "\n";

        if ($this->incident->status_code) {
            $text .= "Код: {$this->incident->status_code}\n";
        }

        return [
            'chat_id' => $notifiable->telegram_chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
    }
}
