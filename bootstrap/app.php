<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'whatsapp.auth' => \App\Http\Middleware\WhatsAppBasicAuth::class,
            'n8n.auth' => \App\Http\Middleware\N8nBasicAuth::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'presets',
            'presets/*',
            'histories',
            'histories/*',
            'send-test',
            'send-message',
            'tracking-session',
            'api/tracking-session',
            'api/tracking/*',
            'api/n8n/*',
            'whatsapp-status',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->expectsJson()
                || $request->is('presets*')
                || $request->is('histories*')
                || $request->is('tracking-session*')
                || $request->is('send-*')
                || $request->is('whatsapp-status')
                || $request->is('health'),
        );
    })->create();
