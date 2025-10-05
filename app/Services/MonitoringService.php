<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\Check;
use App\Models\Incident;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    public function checkMonitor(Monitor $monitor): Check
    {
        $startTime = microtime(true);
        $checkData = [
            'monitor_id' => $monitor->id,
            'checked_at' => now(),
            'check_region' => config('app.check_region', 'default'),
        ];

        try {
            // HTTP перевірка
            $response = $this->performHttpCheck($monitor);

            $checkData = array_merge($checkData, [
                'is_up' => $response['is_up'],
                'status_code' => $response['status_code'],
                'response_time' => (int)((microtime(true) - $startTime) * 1000),
                'final_url' => $response['final_url'] ?? $monitor->url,
                'redirect_count' => $response['redirect_count'] ?? 0,
            ]);

            // SSL перевірка
            if ($monitor->ssl_monitoring && $monitor->type === 'https') {
                $sslData = $this->performSslCheck($monitor);
                $checkData = array_merge($checkData, $sslData);
            }

            // Keyword перевірка
            if ($monitor->keyword && $response['is_up']) {
                $checkData['keyword_found'] = $this->checkKeyword(
                    $response['body'] ?? '',
                    $monitor->keyword
                );
            }

        } catch (\Exception $e) {
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

    private function performHttpCheck(Monitor $monitor): array
    {
        $http = Http::timeout($monitor->timeout)
            ->retry($monitor->retries, 100);

        // Custom headers
        if ($monitor->headers) {
            foreach ($monitor->headers as $key => $value) {
                $http->withHeader($key, $value);
            }
        }

        // Basic auth
        if ($monitor->requires_auth) {
            $http->withBasicAuth($monitor->auth_username, $monitor->auth_password);
        }

        // Виконуємо запит
        $response = match($monitor->request_method) {
            'POST' => $http->post($monitor->url, $monitor->request_body ?? []),
            'HEAD' => $http->head($monitor->url),
            default => $http->get($monitor->url),
        };

        $statusCode = $response->status();
        $expectedCodes = explode(',', $monitor->expected_status_code);

        return [
            'is_up' => in_array($statusCode, $expectedCodes),
            'status_code' => $statusCode,
            'body' => $response->body(),
            'final_url' => $response->effectiveUri()?->__toString(),
            'redirect_count' => count($response->redirects ?? []),
        ];
    }

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
                'ssl_issuer' => $cert['issuer']['CN'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::warning("SSL check failed for {$monitor->url}: {$e->getMessage()}");
            return ['ssl_valid' => false];
        }
    }

    private function checkKeyword(string $body, string $keyword): bool
    {
        return str_contains(strtolower($body), strtolower($keyword));
    }

    private function getErrorType(\Exception $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timeout')) return 'timeout';
        if (str_contains($message, 'dns')) return 'dns';
        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) return 'ssl';
        if (str_contains($message, 'connection')) return 'connection';

        return 'unknown';
    }

    private function updateMonitorStatus(Monitor $monitor, Check $check): void
    {
        $monitor->update([
            'status' => $check->is_up ? 'up' : 'down',
            'last_checked_at' => $check->checked_at,
            'last_status_code' => $check->status_code,
            'last_response_time' => $check->response_time,
        ]);
    }

    private function handleIncidents(Monitor $monitor, Check $check): void
    {
        $currentIncident = $monitor->currentIncident;

        if (!$check->is_up && !$currentIncident) {
            // Сайт впав - створюємо новий інцидент
            $incident = Incident::create([
                'monitor_id' => $monitor->id,
                'status' => 'ongoing',
                'severity' => 'critical',
                'started_at' => now(),
                'detected_at' => now(),
                'status_code' => $check->status_code,
                'error_message' => $check->error_message,
                'error_type' => $check->error_type,
                'failed_checks_count' => 1,
            ]);

            // Відправляємо сповіщення
            $this->sendDownNotification($monitor, $incident);

        } elseif (!$check->is_up && $currentIncident) {
            // Інцидент триває - оновлюємо лічильник
            $currentIncident->increment('failed_checks_count');

        } elseif ($check->is_up && $currentIncident) {
            // Сайт відновився - закриваємо інцидент
            $currentIncident->resolve();

            // Відправляємо сповіщення
            $this->sendUpNotification($monitor, $currentIncident);
        }
    }

    private function sendDownNotification(Monitor $monitor, Incident $incident): void
    {
        if (!$monitor->notifications_enabled) return;

        $monitor->user->notify(new \App\Notifications\SiteDownNotification($monitor, $incident));

        $incident->update([
            'email_sent' => $monitor->user->email_notifications,
            'telegram_sent' => $monitor->user->telegram_enabled,
            'notifications_sent_at' => now(),
        ]);
    }

    private function sendUpNotification(Monitor $monitor, Incident $incident): void
    {
        if (!$monitor->notifications_enabled) return;

        $monitor->user->notify(new \App\Notifications\SiteUpNotification($monitor, $incident));
    }
}
