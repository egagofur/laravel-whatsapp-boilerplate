<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Basic Auth — Admin / API
    |--------------------------------------------------------------------------
    |
    | Required in all environments except when APP_ENV=testing and the
    | test suite injects credentials via phpunit.xml. Empty credentials
    | fail closed (401) — never bypass authentication.
    |
    */

    'basic_auth' => [
        'user' => env('WHATSAPP_BASIC_AUTH_USER'),
        'password' => env('WHATSAPP_BASIC_AUTH_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Basic Auth — n8n Automation API
    |--------------------------------------------------------------------------
    */

    'n8n_auth' => [
        'user' => env('N8N_BASIC_AUTH_USER'),
        'password' => env('N8N_BASIC_AUTH_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (requests per minute)
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'api' => (int) env('RATE_LIMIT_API', 60),
        'send' => (int) env('RATE_LIMIT_SEND', 20),
        'n8n' => (int) env('RATE_LIMIT_N8N', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Sender Backend
    |--------------------------------------------------------------------------
    |
    | web = unofficial WhatsApp Web sidecar (default via whatsapp-web.js)
    | cloud = Meta Cloud API (official Meta Graph API adapter)
    |
    */

    'whatsapp_driver' => env('WHATSAPP_DRIVER', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Outgoing n8n Webhook
    |--------------------------------------------------------------------------
    |
    | Outgoing webhook URL forwarding incoming messages & session readiness.
    | Prefer setting via database setting (/integrations/n8n). Env is fallback.
    |
    */

    'n8n_webhook_url' => env('N8N_WEBHOOK_URL'),

    /*
    | When true, also map sidecar message_create into Laravel MessageReceived.
    | Deduped by message id against the regular message event.
    */
    'n8n_forward_message_create' => env('N8N_FORWARD_MESSAGE_CREATE', true),

    /*
    | Never forward the bot/session's own outbound messages to n8n.
    | If fromMe is forwarded, n8n reply → new fromMe → n8n again = spam loop.
    */
    'n8n_forward_from_me' => env('N8N_FORWARD_FROM_ME', false),

    /*
    | Optional cooldown per chat id (seconds) to absorb double delivery / n8n retries.
    | 0 disables. Applies after fromMe filter.
    */
    'n8n_chat_cooldown_seconds' => (int) env('N8N_CHAT_COOLDOWN_SECONDS', 3),

];
