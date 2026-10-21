#!/bin/sh
set -e

# Ensure persistent SQLite data directory and file exist
mkdir -p /var/www/html/data
if [ ! -f /var/www/html/data/database.sqlite ]; then
    touch /var/www/html/data/database.sqlite
fi

# Ensure persistent session directory and clean up stale Chromium lock files
mkdir -p /var/www/html/storage/app/whatsapp-sidecar/sessions /var/www/html/storage/logs
find /var/www/html/storage/app/whatsapp-sidecar/sessions -name "Singleton*" -delete 2>/dev/null || true
find /var/www/html/storage/app/whatsapp-sidecar/sessions -name "LOCK" -delete 2>/dev/null || true
rm -f /var/www/html/storage/app/whatsapp-sidecar/sidecar.pid

# Fail closed: refuse to start without API auth in production
if [ "${APP_ENV:-local}" = "production" ]; then
    if [ -z "${WHATSAPP_BASIC_AUTH_USER:-}" ] || [ -z "${WHATSAPP_BASIC_AUTH_PASSWORD:-}" ]; then
        echo "ERROR: WHATSAPP_BASIC_AUTH_USER and WHATSAPP_BASIC_AUTH_PASSWORD are required in production."
        exit 1
    fi
    if [ -z "${N8N_BASIC_AUTH_USER:-}" ] || [ -z "${N8N_BASIC_AUTH_PASSWORD:-}" ]; then
        echo "ERROR: N8N_BASIC_AUTH_USER and N8N_BASIC_AUTH_PASSWORD are required in production."
        exit 1
    fi
fi

# Install sidecar node_modules if missing
if [ ! -d /var/www/html/vendor/kstmostofa/laravel-whatsapp/sidecar/node_modules ]; then
    php artisan whatsapp:sidecar:install --skip-chromium || true
fi

# Prefer custom project sidecar when present
if [ -d /var/www/html/whatsapp-sidecar ] && [ ! -d /var/www/html/whatsapp-sidecar/node_modules ]; then
    (cd /var/www/html/whatsapp-sidecar && npm install --omit=dev) || true
fi

# Run database migrations
php artisan migrate --force

# Start the WhatsApp sidecar
php artisan whatsapp:sidecar:start

# Start the queue worker in the background
php artisan queue:work --tries=1 --timeout=0 &

# Start WhatsApp event listeners (required for n8n message.received forward)
# Default: only the default session + extra IDs in DB (skip synthetic "test" to save RAM/logs)
SESSION_NAME=${WHATSAPP_UI_DEFAULT_SESSION:-main}
php artisan whatsapp:web:listen "$SESSION_NAME" &
echo "[start-container] listening on session: $SESSION_NAME"

if [ "${WHATSAPP_LISTEN_TEST_SESSION:-0}" = "1" ] && [ "$SESSION_NAME" != "test" ]; then
    php artisan whatsapp:web:listen test &
    echo "[start-container] listening on session: test"
fi

# Also listen every session id stored in DB (covers non-main session names)
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $ids = Illuminate\Support\Facades\DB::table("wa_sessions")->pluck("id")->all();
} catch (Throwable $e) {
    $ids = [];
}
$default = getenv("WHATSAPP_UI_DEFAULT_SESSION") ?: "main";
$skip = [$default, "test"];
foreach ($ids as $id) {
    if ($id === "" || in_array($id, $skip, true)) continue;
    echo $id, PHP_EOL;
}
' 2>/dev/null | while IFS= read -r sid; do
    [ -z "$sid" ] && continue
    php artisan whatsapp:web:listen "$sid" &
    echo "[start-container] listening on session: $sid"
done

# Run the Laravel web server in the foreground
# Production should terminate TLS at a reverse proxy (see deploy/Caddyfile.example)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-3001}"
