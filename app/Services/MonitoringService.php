<?php

// app/Services/MonitoringService.php
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

        // Базові дані для Check
        $checkData = [
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
        ];

        try {
            // Виконуємо HTTP запит
            $response = $this->performHttpCheck($monitor);

            $checkData = array_merge($checkData, [
                'is_up' => $response['is_up'],
                'status_code' => $response['status_code'],
                'response_time' => (int)((microtime(true) - $startTime) * 1000),
            ]);

            // SSL перевірка для HTTPS
            if ($response['is_up'] && str_starts_with($monitor->url, 'https://')) {
                $sslData = $this->performSslCheck($monitor);
                $checkData = array_merge($checkData, $sslData);
            }

        } catch (\Exception $e) {
            // Помилка при перевірці
            $checkData = array_merge($checkData, [
                'is_up' => false,
                'error_message' => $e->getMessage(),
                'error_type' => $this->getErrorType($e),
            ]);
        }

        // Створюємо запис перевірки
        $check = Check::create($checkData);

        // Оновлюємо статус монітора
        $this->updateMonitorStatus($monitor, $check);

        // Обробляємо інциденти
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
            'is_up' => $response->successful(), // 200-299
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
            // 🔴 Сайт впав - створюємо новий інцидент
            $incident = Incident::create([
                'monitor_id' => $monitor->id,
                'status' => 'ongoing',
                'started_at' => now(),
                'status_code' => $check->status_code,
                'error_message' => $check->error_message,
                'error_type' => $check->error_type,
                'failed_checks_count' => 1,
            ]);

            // Оновлюємо лічильник інцидентів
            $monitor->increment('total_incidents');

            // Надсилаємо сповіщення
            $this->sendDownNotification($monitor, $incident);

        } elseif (!$check->is_up && $currentIncident) {
            // 🔴 Інцидент продовжується
            $currentIncident->increment('failed_checks_count');

        } elseif ($check->is_up && $currentIncident) {
            // 🟢 Сайт відновився - закриваємо інцидент
            $currentIncident->resolve();

            // Надсилаємо сповіщення про відновлення
            $this->sendUpNotification($monitor, $currentIncident);
        }
    }

    /**
     * Надіслати сповіщення про падіння
     */
    private function sendDownNotification(Monitor $monitor, Incident $incident): void
    {
        if (!$monitor->notifications_enabled) {
            return;
        }

        try {
            $monitor->user->notify(new SiteDownNotification($monitor, $incident));

            $incident->update([
                'email_sent' => $monitor->user->email_notifications,
                'telegram_sent' => $monitor->user->telegram_enabled && $monitor->user->telegram_chat_id,
                'notifications_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send down notification: {$e->getMessage()}");
        }
    }

    /**
     * Надіслати сповіщення про відновлення
     */
    private function sendUpNotification(Monitor $monitor, Incident $incident): void
    {
        if (!$monitor->notifications_enabled) {
            return;
        }

        try {
            $monitor->user->notify(new SiteUpNotification($monitor, $incident));
        } catch (\Exception $e) {
            Log::error("Failed to send up notification: {$e->getMessage()}");
        }
    }
}
