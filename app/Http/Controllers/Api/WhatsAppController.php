<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWhatsAppRequest;
use App\Services\WhatsApp\WhatsAppSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppSender $whatsApp
    ) {}

    public function sendTest(Request $request): JsonResponse
    {
        $to = $request->query('to');
        if (! $to) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please specify "to" parameter, e.g. /send-test?to=628123456789',
            ], 400);
        }

        $message = (string) $request->query('message', 'Hello from Laravel WhatsApp sidecar!');
        $session = $request->query('session');

        try {
            $result = $this->whatsApp->send((string) $to, $message, $session ? (string) $session : null);

            return response()->json([
                'status' => 'success',
                'session_used' => $result['session_used'] ?? null,
                'response' => $result['response'] ?? $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            $session = $request->query('session');
            $status = $this->whatsApp->status($session ? (string) $session : null);

            return response()->json(array_merge(['status' => 'success'], $status));
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
