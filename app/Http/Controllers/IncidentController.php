<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Incident;

class IncidentController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    public function index()
    {
        $incidents = Incident::with('monitor')
            ->whereHas('monitor', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest('started_at')
            ->paginate(15)
            ->through(fn($incident) => [
                'id' => $incident->id,
                'monitor' => [
                    'id' => $incident->monitor->id,
                    'name' => $incident->monitor->name,
                    'url' => $incident->monitor->url,
                ],
                'status' => $incident->status,
                'status_color' => $incident->status_color,
                'started_at' => $incident->started_at->format('Y-m-d H:i:s'),
                'resolved_at' => $incident->resolved_at?->format('Y-m-d H:i:s'),
                'duration' => $incident->getDurationFormatted(),
                'error_message' => $incident->error_message,
                'status_code' => $incident->status_code,
            ]);

        return Inertia::render('User/Incidents/Index', [
            'incidents' => $incidents,
        ]);
    }

    public function show(Incident $incident)
    {
        // Перевіряємо що incident належить користувачу
        $this->authorize('view', $incident);

        $incident->load('monitor');

        // Timeline events
        $timeline = [];

        // Incident started
        $timeline[] = [
            'type' => 'started',
            'icon' => 'error',
            'color' => 'red',
            'title' => 'Incident started',
            'time' => $incident->started_at->format('Y-m-d H:i:s'),
        ];

        // Notifications sent
        if ($incident->notifications_sent_at) {
            if ($incident->email_sent) {
                foreach ($incident->monitor->alert_channels ?? [] as $channel) {
                    if ($channel['type'] === 'email') {
                        $timeline[] = [
                            'type' => 'notification',
                            'icon' => 'check',
                            'color' => 'gray',
                            'title' => "Sent out ✉️ Email alert to {$channel['value']}",
                            'time' => $incident->notifications_sent_at->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }

            if ($incident->telegram_sent) {
                foreach ($incident->monitor->alert_channels ?? [] as $channel) {
                    if ($channel['type'] === 'telegram') {
                        // Parse chat_id from value
                        $parts = preg_split('/\s+|:/', $channel['value']);
                        $chatId = end($parts);

                        $timeline[] = [
                            'type' => 'notification',
                            'icon' => 'check',
                            'color' => 'gray',
                            'title' => "Sent out 💬 Telegram alert to {$chatId}",
                            'time' => $incident->notifications_sent_at->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
        }

        // Incident resolved
        if ($incident->resolved_at) {
            $timeline[] = [
                'type' => 'resolved',
                'icon' => 'check',
                'color' => 'green',
                'title' => 'Incident resolved',
                'time' => $incident->resolved_at->format('Y-m-d H:i:s'),
            ];
        }

        return Inertia::render('User/Incidents/Show', [
            'incident' => [
                'id' => $incident->id,
                'status' => $incident->status,
                'status_color' => $incident->status_color,
                'started_at' => $incident->started_at->format('Y-m-d H:i:s'),
                'resolved_at' => $incident->resolved_at?->format('Y-m-d H:i:s'),
                'duration' => $incident->getDurationFormatted(),
                'status_code' => $incident->status_code,
                'error_message' => $incident->error_message,
                'error_type' => $incident->error_type,
                'failed_checks_count' => $incident->failed_checks_count,
                'monitor' => [
                    'id' => $incident->monitor->id,
                    'name' => $incident->monitor->name,
                    'url' => $incident->monitor->url,
                ],
            ],
            'timeline' => $timeline,
        ]);
    }
}
