<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class N8nApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth()
    {
        return $this->withBasicAuth('test_n8n_user', 'test_n8n_pass');
    }

    public function test_send_validates_message(): void
    {
        $this->auth()->postJson('/api/n8n/send', [
            'to' => '628123456789',
        ])->assertStatus(422);
    }

    public function test_send_uses_whatsapp_sender(): void
    {
        $mock = Mockery::mock(WhatsAppSender::class);
        $mock->shouldReceive('send')
            ->once()
            ->with('628123456789', 'hello from n8n', null)
            ->andReturn(['session_used' => 'main', 'response' => ['ok' => true]]);
        $this->app->instance(WhatsAppSender::class, $mock);

        $this->auth()->postJson('/api/n8n/send', [
            'to' => '628123456789',
            'message' => 'hello from n8n',
        ])->assertOk()->assertJsonPath('status', 'success');
    }

    public function test_webhook_settings_roundtrip(): void
    {
        $this->auth()->postJson('/api/n8n/webhook-settings', [
            'n8n_webhook_url' => 'https://hooks.example.com/n8n',
        ])->assertOk();

        $this->assertSame('https://hooks.example.com/n8n', Setting::get('n8n_webhook_url'));

        $this->auth()->getJson('/api/n8n/webhook-settings')
            ->assertOk()
            ->assertJsonPath('n8n_webhook_url', 'https://hooks.example.com/n8n');
    }

    public function test_webhook_test_endpoint_reports_missing_url(): void
    {
        Setting::set('n8n_webhook_url', null);
        config(['whatsapp.n8n_webhook_url' => null]);

        $this->auth()->postJson('/api/n8n/webhook-test')
            ->assertStatus(502)
            ->assertJsonPath('status', 'error');
    }
}
