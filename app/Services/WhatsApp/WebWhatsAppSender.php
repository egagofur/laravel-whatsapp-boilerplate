<?php

namespace App\Services\WhatsApp;

use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;
use Kstmostofa\LaravelWhatsApp\Models\WaSession;

class WebWhatsAppSender implements WhatsAppSender
{
    public function driver(): string
    {
        return 'web';
    }

    public function send(string $to, string $message, ?string $sessionId = null): array
    {
        $to = $this->normalizeRecipient($to);
        $sessionId = $sessionId ?: $this->resolveSessionId();

        $response = WhatsApp::send($to, $message, 'web', $sessionId);

        return [
            'session_used' => $sessionId,
            'response' => $response,
        ];
    }

    public function status(?string $sessionId = null): array
    {
        if ($sessionId) {
            $session = WaSession::find($sessionId);
        } else {
            $session = WaSession::where('status', 'ready')->first()
                ?? WaSession::latest()->first()
                ?? WaSession::find('main');
        }

        $resolvedId = $session?->id ?? ($sessionId ?: 'main');

        $allSessions = WaSession::all()->map(function ($s) {
            return [
                'id' => $s->id,
                'status' => $s->status,
                'phone_number' => $s->phone_number,
                'push_name' => $s->push_name,
                'is_ready' => method_exists($s, 'isReady') ? $s->isReady() : ($s->status === 'ready'),
            ];
        });

        return [
            'server_health' => 'ok',
            'driver' => $this->driver(),
            'active_session_id' => $resolvedId,
            'session_exists' => $session !== null,
            'session_id' => $resolvedId,
            'session_status' => $session?->status ?? 'unknown',
            'phone_number' => $session?->phone_number,
            'push_name' => $session?->push_name,
            'qr_code' => ($session && isset($session->meta['qr'])) ? $session->meta['qr'] : null,
            'is_ready' => $session && method_exists($session, 'isReady')
                ? $session->isReady()
                : ($session?->status === 'ready'),
            'all_sessions' => $allSessions,
        ];
    }

    public function resolveSessionId(?string $requested = null): string
    {
        if ($requested) {
            return $requested;
        }

        $active = WaSession::where('status', 'ready')->first()
            ?? WaSession::latest()->first();

        return $active?->id ?? 'main';
    }

    public function normalizeRecipient(string $to): string
    {
        if (! str_contains($to, '@')) {
            return $to.'@c.us';
        }

        return $to;
    }
}
