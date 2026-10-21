<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class N8nWebhookSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_basic_auth(): void
    {
        $this->get('/integrations/n8n')->assertStatus(401);
    }

    public function test_page_renders_with_auth(): void
    {
        $this->withBasicAuth('test_wa_user', 'test_wa_pass')
            ->get('/integrations/n8n')
            ->assertOk()
            ->assertSee('n8n Webhook', false);
    }

    public function test_livewire_saves_webhook_url(): void
    {
        $this->withBasicAuth('test_wa_user', 'test_wa_pass');

        Livewire::test(\App\Livewire\N8nWebhookSettings::class)
            ->set('n8nWebhookUrl', 'https://hooks.example.com/n8n-test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('statusType', 'success');

        $this->assertSame('https://hooks.example.com/n8n-test', Setting::get('n8n_webhook_url'));
    }

    public function test_livewire_rejects_invalid_url(): void
    {
        $this->withBasicAuth('test_wa_user', 'test_wa_pass');

        Livewire::test(\App\Livewire\N8nWebhookSettings::class)
            ->set('n8nWebhookUrl', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['n8nWebhookUrl']);
    }

    public function test_livewire_can_clear_webhook(): void
    {
        Setting::set('n8n_webhook_url', 'https://hooks.example.com/old');

        $this->withBasicAuth('test_wa_user', 'test_wa_pass');

        Livewire::test(\App\Livewire\N8nWebhookSettings::class)
            ->call('clear')
            ->assertSet('n8nWebhookUrl', '');

        $this->assertNull(Setting::get('n8n_webhook_url'));
    }
}
