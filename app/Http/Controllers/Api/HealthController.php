<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __construct(
        private readonly WhatsAppSender $whatsApp
    ) {}

    /**
     * Deep health check for Docker / load balancers.
     * GET /health — does not require Basic Auth.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => false,
            'whatsapp_driver' => $this->whatsApp->driver(),
            'whatsapp_ready' => false,
        ];

        $statusCode = 200;

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable) {
            $checks['database'] = false;
            $statusCode = 503;
        }

        try {
            $wa = $this->whatsApp->status();
            $checks['whatsapp_ready'] = (bool) ($wa['is_ready'] ?? false);
            $checks['session_status'] = $wa['session_status'] ?? null;
            $checks['session_id'] = $wa['session_id'] ?? null;
        } catch (Throwable $e) {
            $checks['whatsapp_error'] = $e->getMessage();
            // WA not ready is degraded but not always fatal for HTTP serving
        }

        return response()->json([
            'status' => $statusCode === 200 ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }
}
