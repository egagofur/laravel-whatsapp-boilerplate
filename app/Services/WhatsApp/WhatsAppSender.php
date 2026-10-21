<?php

namespace App\Services\WhatsApp;

interface WhatsAppSender
{
    /**
     * Send a text message via the configured WhatsApp backend.
     *
     * @return array<string, mixed>
     */
    public function send(string $to, string $message, ?string $sessionId = null): array;

    /**
     * Session / backend health snapshot for dashboards and n8n.
     *
     * @return array<string, mixed>
     */
    public function status(?string $sessionId = null): array;

    /**
     * Driver name: web | cloud
     */
    public function driver(): string;
}
