<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">n8n Webhook (Outgoing)</flux:heading>
            <flux:subheading>
                Atur URL webhook n8n yang menerima event dari WA service
                (<code class="text-xs">message.received</code>, <code class="text-xs">session.ready</code>).
            </flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="url($waPrefix)" variant="ghost" size="sm" icon="arrow-left">
                WhatsApp UI
            </flux:button>
            <flux:button :href="url($waPrefix.'/webhooks')" variant="ghost" size="sm" icon="bolt">
                Log Webhooks (package)
            </flux:button>
        </div>
    </div>

    <flux:callout variant="warning" icon="exclamation-triangle">
        <flux:callout.heading>Kalau chat tidak memicu n8n</flux:callout.heading>
        <flux:callout.text>
            1) Klik <strong>Kirim test ke n8n</strong> di bawah (uji koneksi URL saja).<br>
            2) Event chat butuh process <code class="text-xs">whatsapp:web:listen</code> (otomatis di Docker).<br>
            3) Event <code class="text-xs">message</code> WhatsApp Web biasanya <strong>tidak</strong> fire untuk pesan yang Anda kirim sendiri
            (<code class="text-xs">fromMe</code>) — uji dengan chat <strong>dari HP lain ke nomor session</strong>.
            Service sekarang juga listen <code class="text-xs">message_create</code> (termasuk fromMe), setelah redeploy.<br>
            4) Cek log container: <code class="text-xs">n8n webhook delivered</code> / <code class="text-xs">n8n webhook request failed</code>.
        </flux:callout.text>
    </flux:callout>

    <flux:callout variant="info" icon="information-circle">
        <flux:callout.heading>Halaman custom app — tidak mengubah library</flux:callout.heading>
        <flux:callout.text>
            Form ini milik aplikasi WA Tracker. Menu package <strong>Webhooks</strong> = log pesan, bukan form URL n8n.
            Production URL n8n harus berbentuk
            <code class="text-xs">https://…/webhook/…</code> (bukan <code class="text-xs">/webhook-test/</code>)
            dan workflow harus <strong>Active</strong>.
        </flux:callout.text>
    </flux:callout>

    @if ($statusMessage)
        <flux:callout :variant="$statusType === 'success' ? 'success' : 'danger'" icon="check-circle">
            <flux:callout.text>{{ $statusMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input
                wire:model="n8nWebhookUrl"
                label="n8n Webhook URL"
                type="url"
                placeholder="https://n8n.example.com/webhook/xxxxxxxx"
                description="Contoh production: https://n8n.dot.co.id/webhook/wa-service"
            />

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary" icon="check">
                    Simpan
                </flux:button>
                <flux:button type="button" variant="filled" wire:click="sendTest" icon="paper-airplane" wire:loading.attr="disabled">
                    Kirim test ke n8n
                </flux:button>
                <flux:button type="button" variant="ghost" wire:click="clear" icon="trash" wire:confirm="Hapus URL webhook dari database?">
                    Hapus
                </flux:button>
            </div>
        </form>
    </flux:card>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:card>
            <flux:heading size="sm" class="mb-3">Status efektif</flux:heading>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">Sumber aktif</dt>
                    <dd class="font-medium">
                        @if ($hasDbValue)
                            Database (<code class="text-xs">settings.n8n_webhook_url</code>)
                        @elseif ($envFallback !== '')
                            Environment / config (<code class="text-xs">N8N_WEBHOOK_URL</code>)
                        @else
                            Tidak dikonfigurasi
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-zinc-500">URL efektif</dt>
                    <dd class="font-mono text-xs break-all">
                        {{ $effectiveUrl !== '' ? $effectiveUrl : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Fallback config</dt>
                    <dd class="font-mono text-xs break-all">
                        {{ $envFallback !== '' ? $envFallback : '—' }}
                    </dd>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="sm" class="mb-3">Event yang di-forward</flux:heading>
            <ul class="list-disc space-y-2 pl-5 text-sm text-zinc-600 dark:text-zinc-300">
                <li>
                    <code class="text-xs">message.received</code>
                    — chat masuk (default: <strong>skip fromMe</strong> agar balasan bot tidak loop)
                </li>
                <li>
                    <code class="text-xs">session.ready</code>
                    — session Web siap / re-auth sukses
                </li>
            </ul>
            <flux:text class="mt-3 !text-xs !text-zinc-500">
                Di n8n tetap disarankan filter <code class="text-xs">message.fromMe === false</code>
                sebelum AI / send balasan.
            </flux:text>
            <flux:text class="mt-4 !text-xs !text-zinc-500">
                Di Docker, <code class="text-xs">start-container.sh</code> menjalankan
                <code class="text-xs">whatsapp:web:listen</code> untuk session default + <code class="text-xs">test</code>
                dan semua session di DB.
            </flux:text>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="sm" class="mb-3">Sample payload (tombol test)</flux:heading>
        <flux:text class="!text-sm !mb-2">
            Field penting untuk node balasan: pakai
            <code class="text-xs">message.reply_to</code> atau
            <code class="text-xs">message.phone_number</code>
            (bukan hanya <code class="text-xs">message.from</code> yang bisa @lid).
        </flux:text>
        <pre class="overflow-x-auto rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-900">{
  "event": "message.received",
  "session_id": "main",
  "source": "manual_test",
  "message": {
    "from": "84203018399886@lid",
    "to": "6285655571269@c.us",
    "body": "Test ping...",
    "fromMe": false,
    "from_lid": "84203018399886@lid",
    "phone_number": "6281234567890",
    "reply_to": "6281234567890@c.us"
  }
}</pre>
    </flux:card>

    <flux:card>
        <flux:heading size="sm" class="mb-3">API (otomatisasi / n8n)</flux:heading>
        <flux:text class="!text-sm">
            Basic Auth: <code class="text-xs">N8N_BASIC_AUTH_USER</code> /
            <code class="text-xs">N8N_BASIC_AUTH_PASSWORD</code>
        </flux:text>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-900">GET  /api/n8n/webhook-settings
POST /api/n8n/webhook-settings
     { "n8n_webhook_url": "https://..." }
POST /api/n8n/webhook-test   (payload sample lengkap ke URL tersimpan)</pre>
    </flux:card>
</div>
