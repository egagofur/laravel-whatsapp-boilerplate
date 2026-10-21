<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\N8nWebhookForwarder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class N8nWebhookForwarderTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_url_prefers_database(): void
    {
        config(['whatsapp.n8n_webhook_url' => 'https://env.example.com/hook']);
        Setting::set('n8n_webhook_url', 'https://db.example.com/hook');

        $forwarder = new N8nWebhookForwarder;
        $this->assertSame('https://db.example.com/hook', $forwarder->resolveUrl());
    }

    public function test_post_returns_error_when_unconfigured(): void
    {
        config(['whatsapp.n8n_webhook_url' => null]);
        $result = (new N8nWebhookForwarder)->post(['event' => 'x']);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('not configured', (string) $result['error']);
    }

    public function test_skips_from_me_messages_by_default(): void
    {
        config([
            'whatsapp.n8n_webhook_url' => 'https://hooks.example.com/n8n',
            'whatsapp.n8n_forward_from_me' => false,
            'whatsapp.n8n_chat_cooldown_seconds' => 0,
        ]);

        $result = (new N8nWebhookForwarder)->forwardMessage('main', [
            'id' => 'true_xxx',
            'from' => '6281@c.us',
            'body' => 'bot reply',
            'fromMe' => true,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('skipped_from_me', $result['body']);
    }

    public function test_sample_payload_includes_lid_enrichment_fields(): void
    {
        $payload = (new N8nWebhookForwarder)->sampleInboundPayload();

        $this->assertSame('message.received', $payload['event']);
        $this->assertArrayHasKey('from_lid', $payload['message']);
        $this->assertArrayHasKey('phone_number', $payload['message']);
        $this->assertArrayHasKey('reply_to', $payload['message']);
        $this->assertStringEndsWith('@lid', (string) $payload['message']['from']);
        $this->assertStringEndsWith('@c.us', (string) $payload['message']['reply_to']);
        $this->assertFalse($payload['message']['fromMe']);
    }
}
