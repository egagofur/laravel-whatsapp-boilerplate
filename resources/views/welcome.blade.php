<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'WhatsApp Service Boilerplate') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background-color: #0c0e12;
            color: #f3f4f6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .container {
            max-width: 760px;
            width: 90%;
            margin: 2rem auto;
            background: rgba(22, 27, 34, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            backdrop-filter: blur(12px);
            padding: 2.5rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }
        .pulse {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 10px #22c55e;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.025em;
        }
        p.subtitle {
            color: #9ca3af;
            font-size: 1rem;
            line-height: 1.5;
            margin: 0 0 2rem 0;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }
        .card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(34, 197, 94, 0.4);
            transform: translateY(-2px);
        }
        .card-title {
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }
        .card-desc {
            font-size: 0.85rem;
            color: #9ca3af;
            line-height: 1.4;
        }
        .endpoints {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            padding: 1.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
        }
        .endpoint-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .endpoint-row:last-child {
            border-bottom: none;
        }
        .method {
            font-weight: 600;
            padding: 0.15rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .method.post { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .method.get { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .path { color: #e5e7eb; }
        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">
            <span class="pulse"></span>
            WhatsApp Service · Online & Ready
        </div>
        <h1>WhatsApp Service Boilerplate</h1>
        <p class="subtitle">Production-ready Laravel 12 API with bundled Node.js WhatsApp Web sidecar, n8n webhook forwarder, and Docker support.</p>

        <div class="grid">
            <a href="/whatsapp" class="card">
                <div class="card-title">WhatsApp Admin UI <span>→</span></div>
                <div class="card-desc">Scan QR code, manage active sessions, and review message logs.</div>
            </a>
            <a href="/integrations/n8n" class="card">
                <div class="card-title">n8n Integration <span>→</span></div>
                <div class="card-desc">Configure outgoing webhook URL and test payload delivery.</div>
            </a>
            <a href="/health" class="card" target="_blank">
                <div class="card-title">Health Check <span>↗</span></div>
                <div class="card-desc">Deep diagnostic endpoint for sidecar, sqlite, and runtime.</div>
            </a>
        </div>

        <div class="endpoints">
            <div style="font-weight: 600; margin-bottom: 0.75rem; color: #9ca3af; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Core API Endpoints</div>
            <div class="endpoint-row">
                <span class="method post">POST</span>
                <span class="path">/send-message</span>
                <span style="color: #6b7280; margin-left: auto;">Send message via sidecar</span>
            </div>
            <div class="endpoint-row">
                <span class="method get">GET</span>
                <span class="path">/whatsapp-status</span>
                <span style="color: #6b7280; margin-left: auto;">Get sidecar connection status</span>
            </div>
            <div class="endpoint-row">
                <span class="method post">POST</span>
                <span class="path">/api/n8n/send</span>
                <span style="color: #6b7280; margin-left: auto;">n8n webhook sender</span>
            </div>
            <div class="endpoint-row">
                <span class="method get">GET</span>
                <span class="path">/api/n8n/status</span>
                <span style="color: #6b7280; margin-left: auto;">n8n health query</span>
            </div>
        </div>

        <div class="footer">
            Built with Laravel 12 &middot; whatsapp-web.js &middot; Livewire & Flux
        </div>
    </div>
</body>
</html>
