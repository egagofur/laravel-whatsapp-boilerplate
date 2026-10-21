<?php

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * Placeholder Meta Cloud API adapter.
 *
 * Wire WHATSAPP_DRIVER=cloud only after phone_number_id + access_token
 * and message templates are configured. See docs/WA_BACKEND_STRATEGY.md.
 */
class CloudWhatsAppSender implements WhatsAppSender
{
    public function driver(): string
    {
        return 'cloud';
    }

    public function send(string $to, string $message, ?string $sessionId = null): array
    {
        throw new RuntimeException(
            'Cloud WhatsApp driver is not fully configured. Set Meta credentials and implement template/send mapping, or use WHATSAPP_DRIVER=web.'
        );
    }

    public function status(?string $sessionId = null): array
    {
        $configured = filled(config('laravel-whatsapp.phone_number_id'))
            && filled(config('laravel-whatsapp.access_token'));

        return [
            'server_health' => $configured ? 'ok' : 'misconfigured',
            'driver' => $this->driver(),
            'active_session_id' => null,
            'session_exists' => false,
            'session_id' => null,
            'session_status' => $configured ? 'configured' : 'missing_credentials',
            'phone_number' => config('laravel-whatsapp.phone_number_id'),
            'push_name' => null,
            'qr_code' => null,
            'is_ready' => $configured,
            'all_sessions' => [],
        ];
    }
}
