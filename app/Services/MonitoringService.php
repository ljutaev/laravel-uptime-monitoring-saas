<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\Check;
use App\Models\Incident;
use App\Notifications\SiteDownNotification;
use App\Notifications\SiteUpNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    /**
     * Виконати перевірку монітора
     */
    public function checkMonitor(Monitor $monitor): Check
    {
        $startTime = microtime(true);

        $checkData = [
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
        ];

        try {
            $response = $this->performHttpCheck($monitor);

            $checkData = array_merge($checkData, [
                'is_up' => $response['is_up'],
                'status_code' => $response['status_code'],
                'response_time' => (int)((microtime(true) - $startTime) * 1000),
            ]);

            if ($response['is_up'] && str_starts_with($monitor->url, 'https://')) {
                $sslData = $this->performSslCheck($monitor);
                $checkData = array_merge($checkData, $sslData);
            }

        } catch (\Exception $e) {
            $checkData = array_merge($checkData, [
                'is_up' => false,
                'error_message' => $e->getMessage(),
                'error_type' => $this->getErrorType($e),
            ]);
        }

        $check = Check::create($checkData);
        $this->updateMonitorStatus($monitor, $check);
        $this->handleIncidents($monitor, $check);

        return $check;
    }

    /**
     * HTTP перевірка
     */
    private function performHttpCheck(Monitor $monitor): array
    {
        $response = Http::timeout($monitor->timeout)
            ->retry(3, 100)
            ->get($monitor->url);

        $statusCode = $response->status();

        return [
            'is_up' => $response->successful(),
            'status_code' => $statusCode,
        ];
    }

    /**
     * SSL перевірка
     */
    private function performSslCheck(Monitor $monitor): array
    {
        try {
            $url = parse_url($monitor->url);
            $host = $url['host'];
            $port = $url['port'] ?? 443;

            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $stream = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($stream === false) {
                return ['ssl_valid' => false];
            }

            $params = stream_context_get_params($stream);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            fclose($stream);

            if (!$cert) {
                return ['ssl_valid' => false];
            }

            return [
                'ssl_valid' => true,
                'ssl_expires_at' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
            ];

        } catch (\Exception $e) {
            Log::warning("SSL check failed for {$monitor->url}: {$e->getMessage()}");
            return ['ssl_valid' => false];
        }
    }

    /**
     * Визначити тип помилки
     */
    private function getErrorType(\Exception $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timeout')) return 'timeout';
        if (str_contains($message, 'dns')) return 'dns';
        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) return 'ssl';
        if (str_contains($message, 'connection')) return 'connection';

        return 'unknown';
    }

    /**
     * Оновити статус монітора
     */
    private function updateMonitorStatus(Monitor $monitor, Check $check): void
    {
        $monitor->update([
            'status' => $check->is_up ? 'up' : 'down',
            'last_checked_at' => $check->checked_at,
            'last_status_code' => $check->status_code,
            'last_response_time' => $check->response_time,
        ]);
    }

    /**
     * Обробити інциденти
     */
    private function handleIncidents(Monitor $monitor, Check $check): void
    {
        $currentIncident = $monitor->currentIncident;

        if (!$check->is_up && !$currentIncident) {
            $incident = Incident::create([
                'monitor_id' => $monitor->id,
                'status' => 'ongoing',
                'started_at' => now(),
                'status_code' => $check->status_code,
                'error_message' => $check->error_message,
                'error_type' => $check->error_type,
                'failed_checks_count' => 1,
            ]);

            $monitor->increment('total_incidents');
            $this->sendNotifications($monitor, $incident, 'down');

        } elseif (!$check->is_up && $currentIncident) {
            $currentIncident->increment('failed_checks_count');

        } elseif ($check->is_up && $currentIncident) {
            $currentIncident->resolve();
            $this->sendNotifications($monitor, $currentIncident, 'up');
        }
    }

    /**
     * Відправити сповіщення
     */
    private function sendNotifications(Monitor $monitor, Incident $incident, string $type): void
    {
        if (!$monitor->notifications_enabled || !$monitor->alert_channels) {
            Log::info('Notifications disabled or no channels configured', [
                'monitor_id' => $monitor->id,
            ]);
            return;
        }

        Log::info('Sending ' . $type . ' notifications', [
            'monitor_id' => $monitor->id,
            'incident_id' => $incident->id,
            'channels' => $monitor->alert_channels,
        ]);

        $emailSent = false;
        $telegramSent = false;

        // Відправляємо сповіщення через всі канали
        foreach ($monitor->alert_channels as $channel) {
            try {
                if ($channel['type'] === 'email') {
                    $this->sendEmailNotification($monitor, $incident, $channel['value'], $type);
                    $emailSent = true;
                    Log::info('Email sent successfully', [
                        'email' => $channel['value'],
                        'incident_id' => $incident->id,
                    ]);
                }

                if ($channel['type'] === 'telegram') {
                    $success = $this->sendTelegramNotification($monitor, $incident, $channel['value'], $type);
                    if ($success) {
                        $telegramSent = true;
                        Log::info('Telegram sent successfully', [
                            'incident_id' => $incident->id,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to send {$type} notification", [
                    'channel_type' => $channel['type'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Оновлюємо incident ОДИН РАЗ після всіх відправок
        $incident->update([
            'notifications_sent_at' => now(),
            'email_sent' => $emailSent,
            'telegram_sent' => $telegramSent,
        ]);

        Log::info('Incident notification status updated', [
            'incident_id' => $incident->id,
            'email_sent' => $emailSent,
            'telegram_sent' => $telegramSent,
        ]);
    }

    /**
     * Відправити email сповіщення
     */
    private function sendEmailNotification(Monitor $monitor, Incident $incident, string $email, string $type): void
    {
        $notificationClass = $type === 'down' ? SiteDownNotification::class : SiteUpNotification::class;
        \Notification::route('mail', $email)->notify(new $notificationClass($monitor, $incident));
    }

    /**
     * Відправити Telegram сповіщення
     */
    private function sendTelegramNotification(Monitor $monitor, Incident $incident, string $value, string $type): bool
    {
        $cleanValue = str_starts_with($value, 'api:')
            ? substr($value, 4)
            : $value;

        if (str_contains($cleanValue, ' ')) {
            $parts = explode(' ', $cleanValue, 2);
        } elseif (substr_count($cleanValue, ':') >= 2) {
            $lastColon = strrpos($cleanValue, ':');
            $parts = [
                substr($cleanValue, 0, $lastColon),
                substr($cleanValue, $lastColon + 1)
            ];
        } else {
            Log::error("Invalid Telegram format", ['value' => $value]);
            return false;
        }

        if (count($parts) !== 2) {
            Log::error("Invalid Telegram format", ['value' => $value]);
            return false;
        }

        $token = $parts[0];
        $chatId = $parts[1];

        $text = $type === 'down'
            ? $this->buildTelegramDownMessage($monitor, $incident)
            : $this->buildTelegramUpMessage($monitor, $incident);

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            if (!$response->successful()) {
                Log::error("Telegram API error", [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Telegram exception", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Побудувати повідомлення про падіння
     */
    private function buildTelegramDownMessage(Monitor $monitor, Incident $incident): string
    {
        $text = "🔴 *Сайт недоступний*\n\n";
        $text .= "Сайт: *{$monitor->name}*\n";
        $text .= "URL: {$monitor->url}\n";
        $text .= "Час: " . $incident->started_at->format('d.m.Y H:i') . "\n";

        if ($incident->status_code) {
            $text .= "Код: {$incident->status_code}\n";
        }

        if ($incident->error_message) {
            $text .= "Помилка: {$incident->error_message}\n";
        }

        return $text;
    }

    /**
     * Побудувати повідомлення про відновлення
     */
    private function buildTelegramUpMessage(Monitor $monitor, Incident $incident): string
    {
        $downtime = $incident->getDurationFormatted();

        $text = "✅ *Сайт відновлено*\n\n";
        $text .= "Сайт: *{$monitor->name}*\n";
        $text .= "URL: {$monitor->url}\n";
        $text .= "Час простою: {$downtime}\n";
        $text .= "Відновлено: " . $incident->resolved_at->format('d.m.Y H:i');

        return $text;
    }
}
