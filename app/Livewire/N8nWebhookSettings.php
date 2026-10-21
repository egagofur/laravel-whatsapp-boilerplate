<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\N8nWebhookForwarder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * App-owned admin UI for outgoing n8n webhook URL.
 * Reuses kstmostofa/laravel-whatsapp layout chrome.
 */
#[Title('WhatsApp Service · n8n Webhook')]
#[Layout('laravel-whatsapp::layouts.app')]
class N8nWebhookSettings extends Component
{
    public string $n8nWebhookUrl = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->n8nWebhookUrl = (string) (
            Setting::get('n8n_webhook_url')
            ?? config('whatsapp.n8n_webhook_url')
            ?? ''
        );
    }

    public function save(): void
    {
        $this->validate([
            'n8nWebhookUrl' => ['nullable', 'url', 'max:2048'],
        ], [
            'n8nWebhookUrl.url' => 'URL webhook harus valid (contoh: https://n8n.example.com/webhook/xxx).',
        ]);

        $url = trim($this->n8nWebhookUrl);
        Setting::set('n8n_webhook_url', $url !== '' ? $url : null);

        $this->n8nWebhookUrl = $url;
        $this->statusType = 'success';
        $this->statusMessage = $url === ''
            ? 'Webhook n8n dikosongkan. Event tidak akan di-forward (kecuali N8N_WEBHOOK_URL di .env).'
            : 'Webhook n8n berhasil disimpan.';
    }

    public function clear(): void
    {
        Setting::set('n8n_webhook_url', null);
        $this->n8nWebhookUrl = '';
        $this->statusType = 'success';
        $this->statusMessage = 'Webhook n8n dihapus dari database.';
    }

    public function sendTest(N8nWebhookForwarder $forwarder): void
    {
        // Prefer URL currently shown in the form (even if not saved yet)
        $url = trim($this->n8nWebhookUrl) ?: null;
        if ($url) {
            $this->validate([
                'n8nWebhookUrl' => ['url', 'max:2048'],
            ]);
        }

        $result = $forwarder->sendTestPing($url);

        if ($result['ok']) {
            $this->statusType = 'success';
            $this->statusMessage = 'Test webhook OK (HTTP '.($result['status'] ?? '?').'). '
                .'Payload menyertakan from_lid, phone_number, reply_to — cek Executions di n8n untuk map node.';
        } else {
            $this->statusType = 'danger';
            $this->statusMessage = 'Test webhook GAGAL: '.($result['error'] ?? 'unknown')
                .' — URL: '.($result['url'] ?? '—')
                .($result['body'] ? ' — body: '.mb_substr((string) $result['body'], 0, 200) : '');
        }
    }

    public function render(N8nWebhookForwarder $forwarder)
    {
        $envFallback = (string) (config('whatsapp.n8n_webhook_url') ?: '');
        $dbValue = Setting::get('n8n_webhook_url');
        $effective = $forwarder->resolveUrl() ?: '';

        return view('livewire.n8n-webhook-settings', [
            'envFallback' => $envFallback,
            'hasDbValue' => filled($dbValue),
            'effectiveUrl' => $effective,
            'waPrefix' => config('laravel-whatsapp.ui.route_prefix', 'whatsapp'),
        ]);
    }
}
