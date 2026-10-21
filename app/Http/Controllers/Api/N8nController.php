<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWhatsAppRequest;
use App\Http\Requests\UpdateWebhookSettingsRequest;
use App\Models\Setting;
use App\Services\N8nWebhookForwarder;
use App\Services\WhatsApp\WhatsAppSender;
use Illuminate\Http\JsonResponse;
use Throwable;

class N8nController extends Controller
{
    public function __construct(
        private readonly WhatsAppSender $whatsApp,
        private readonly N8nWebhookForwarder $webhookForwarder,
    ) {}

    /**
     * Send WhatsApp message via n8n automation webhook.
     */
    public function send(SendWhatsAppRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->whatsApp->send(
                $data['to'],
                $data['message'],
                $data['session'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'session_used' => $result['session_used'] ?? null,
                'response' => $result['response'] ?? $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'session_used' => null,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check status of WhatsApp session & sidecar health.
     */
    public function status(): JsonResponse
    {
        try {
            $status = $this->whatsApp->status();

            return response()->json([
                'status' => 'success',
                'server_health' => $status['server_health'] ?? 'ok',
                'session_id' => $status['session_id'] ?? null,
                'session_status' => $status['session_status'] ?? null,
                'phone_number' => $status['phone_number'] ?? null,
                'push_name' => $status['push_name'] ?? null,
                'is_ready' => $status['is_ready'] ?? false,
                'driver' => $status['driver'] ?? null,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get configured outgoing n8n webhook URL.
     */
    public function webhookSettings(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'n8n_webhook_url' => Setting::get('n8n_webhook_url'),
        ]);
    }

    /**
     * Update configured outgoing n8n webhook URL.
     */
    public function updateWebhookSettings(UpdateWebhookSettingsRequest $request): JsonResponse
    {
        $url = $request->validated()['n8n_webhook_url'] ?? null;
        Setting::set('n8n_webhook_url', $url);

        return response()->json([
            'status' => 'success',
            'message' => 'N8N Webhook URL updated successfully',
            'n8n_webhook_url' => $url,
        ]);
    }

    /**
     * Fire a synthetic webhook POST to verify n8n connectivity from the server.
     */
    public function testWebhook(): JsonResponse
    {
        $result = $this->webhookForwarder->sendTestPing();

        return response()->json([
            'status' => $result['ok'] ? 'success' : 'error',
            'result' => $result,
        ], $result['ok'] ? 200 : 502);
    }
}
