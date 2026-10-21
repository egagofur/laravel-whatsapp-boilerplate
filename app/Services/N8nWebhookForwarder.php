<?php

namespace App\Services;

use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class N8nWebhookForwarder
{
    public function resolveUrl(): ?string
    {
        $fromDb = Setting::get('n8n_webhook_url');
        if (filled($fromDb)) {
            return (string) $fromDb;
        }

        // Prefer config over bare env() so config:cache still works.
        $fromConfig = config('whatsapp.n8n_webhook_url');
        if (filled($fromConfig)) {
            return (string) $fromConfig;
        }

        $fromEnv = env('N8N_WEBHOOK_URL');

        return filled($fromEnv) ? (string) $fromEnv : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: int|null, body: string|null, error: string|null, url: string|null}
     */
    public function post(array $payload, ?string $url = null): array
    {
        $url = $url ?: $this->resolveUrl();
        if (! $url) {
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'n8n webhook URL is not configured',
                'url' => null,
            ];
        }

        // Deduplicate rapid double-fires (message + message_create for same id)
        $messageId = data_get($payload, 'message.id');
        if (is_string($messageId) && $messageId !== '') {
            $dedupeKey = 'n8n_webhook_dedupe:'.$messageId;
            if (! Cache::add($dedupeKey, 1, now()->addMinutes(2))) {
                Log::info('n8n webhook skipped (duplicate message id)', ['id' => $messageId]);

                return [
                    'ok' => true,
                    'status' => 200,
                    'body' => 'skipped_duplicate',
                    'error' => null,
                    'url' => $url,
                ];
            }
        }

        try {
            $client = new Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);

            $response = $client->post($url, [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'wa-service-n8n-forwarder/1.0',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $ok = $status >= 200 && $status < 300;

            if ($ok) {
                Log::info('n8n webhook delivered', [
                    'event' => $payload['event'] ?? null,
                    'session_id' => $payload['session_id'] ?? null,
                    'status' => $status,
                    'url' => $url,
                ]);
            } else {
                Log::error('n8n webhook non-2xx response', [
                    'event' => $payload['event'] ?? null,
                    'status' => $status,
                    'body' => mb_substr($body, 0, 500),
                    'url' => $url,
                ]);
            }

            return [
                'ok' => $ok,
                'status' => $status,
                'body' => mb_substr($body, 0, 1000),
                'error' => $ok ? null : "HTTP {$status}",
                'url' => $url,
            ];
        } catch (GuzzleException $e) {
            Log::error('n8n webhook request failed', [
                'event' => $payload['event'] ?? null,
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{ok: bool, status: int|null, body: string|null, error: string|null, url: string|null}
     */
    public function forwardMessage(string $sessionId, array $message, string $source = 'message'): array
    {
        $fromMe = (bool) ($message['fromMe'] ?? false);

        // Critical: bot replies are fromMe=true. Forwarding them re-triggers n8n → infinite spam.
        if ($fromMe && ! config('whatsapp.n8n_forward_from_me', false)) {
            Log::info('n8n webhook skipped (fromMe)', [
                'session_id' => $sessionId,
                'message_id' => $message['id'] ?? null,
                'source' => $source,
            ]);

            return [
                'ok' => true,
                'status' => 200,
                'body' => 'skipped_from_me',
                'error' => null,
                'url' => $this->resolveUrl(),
            ];
        }

        // Short per-chat cooldown to absorb double fires / rapid retries for same peer.
        $cooldown = (int) config('whatsapp.n8n_chat_cooldown_seconds', 0);
        $chatKey = (string) ($message['from'] ?? $message['to'] ?? '');
        if ($cooldown > 0 && $chatKey !== '') {
            $key = 'n8n_chat_cooldown:'.$sessionId.':'.$chatKey;
            if (! Cache::add($key, 1, now()->addSeconds($cooldown))) {
                Log::info('n8n webhook skipped (chat cooldown)', [
                    'session_id' => $sessionId,
                    'chat' => $chatKey,
                    'cooldown' => $cooldown,
                ]);

                return [
                    'ok' => true,
                    'status' => 200,
                    'body' => 'skipped_chat_cooldown',
                    'error' => null,
                    'url' => $this->resolveUrl(),
                ];
            }
        }

        return $this->post([
            'event' => 'message.received',
            'session_id' => $sessionId,
            'source' => $source,
            'message' => $message,
        ]);
    }

    /**
     * @return array{ok: bool, status: int|null, body: string|null, error: string|null, url: string|null}
     */
    public function forwardSessionReady(string $sessionId): array
    {
        return $this->post([
            'event' => 'session.ready',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Sample inbound payload shaped like real WhatsApp Web events (incl. @lid).
     * Use this for n8n node mapping / test executions.
     *
     * @return array{event: string, session_id: string, source: string, message: array<string, mixed>}
     */
    public function sampleInboundPayload(): array
    {
        $phone = '6281234567890';
        $lid = '84203018399886@lid';
        $eSim = '6285655571269@c.us';

        return [
            'event' => 'message.received',
            'session_id' => 'main',
            'source' => 'manual_test',
            'message' => [
                'id' => 'true_'.$lid.'_TEST_'.uniqid(),
                // Real WA often delivers from as Linked ID, not phone JID
                'from' => $lid,
                'to' => $eSim,
                'body' => 'Test ping dari WA service /integrations/n8n',
                'type' => 'chat',
                'timestamp' => time(),
                'hasMedia' => false,
                'isForwarded' => false,
                'isStatus' => false,
                'isStarred' => false,
                'fromMe' => false,
                'author' => null,
                'deviceType' => 'android',
                // Enrichment fields (sidecar) — map these in n8n
                'from_lid' => $lid,
                'phone_number' => $phone,
                'reply_to' => $phone.'@c.us',
            ],
        ];
    }

    /**
     * Send a synthetic payload so operators can verify n8n connectivity
     * and map new fields (reply_to, phone_number, from_lid) in the workflow.
     *
     * @return array{ok: bool, status: int|null, body: string|null, error: string|null, url: string|null}
     */
    public function sendTestPing(?string $url = null): array
    {
        return $this->post($this->sampleInboundPayload(), $url);
    }
}

