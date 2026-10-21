# Laravel WhatsApp Service Boilerplate

Production-ready Laravel 12 service with a bundled **WhatsApp Web sidecar** (Node.js via `whatsapp-web.js`), **n8n automation integration**, and single-container **Docker deployment**.

---

## ⚡ Key Features

- **Dual-Driver Architecture**: Supports unofficial WhatsApp Web (Puppeteer/Chromium) and official Meta Cloud API seamlessly.
- **Admin Dashboard UI**: Built-in Livewire & Flux interface (`/whatsapp`) for scanning QR codes, monitoring session state, and reviewing message logs.
- **n8n Integration**:
  - Inbound API: Send messages and query status via `/api/n8n/*`.
  - Outbound Webhook: Automatically forward incoming WhatsApp messages and session readiness events to your n8n workflow.
  - Webhook Config UI: Adjust or test n8n webhook URLs dynamically via `/integrations/n8n`.
- **Hardened Security**: Fail-closed HTTP Basic Authentication for admin and n8n APIs with per-route rate limiting.
- **Single Container Docker Deployment**: Runs PHP 8.4, Node 20, headless Chromium, and SQLite in a self-healing Docker image.

---

## 🏗️ Architecture

```
Client / Admin  --Basic Auth-->  Laravel API  --Bearer-->  whatsapp-sidecar (whatsapp-web.js)
                                      |
                                      +--> SQLite (sessions, messages, settings)
                                      +--> Livewire Admin UI (/whatsapp)
                                      +--> n8n Webhook Settings (/integrations/n8n)
n8n Workflow    <---Webhook---+-------+
n8n Workflow    ---Basic Auth-->  /api/n8n/send
```

---

## 📂 Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   # WhatsAppController, N8nController, HealthController
│   │   ├── Middleware/        # WhatsAppBasicAuth, N8nBasicAuth
│   │   └── Requests/          # SendWhatsAppRequest, UpdateWebhookSettingsRequest
│   ├── Livewire/              # N8nWebhookSettings UI component
│   ├── Models/                # User, Setting
│   ├── Providers/             # AppServiceProvider (Rate limiting & Event listeners)
│   └── Services/              # WhatsAppSender (Web & Cloud adapters), N8nWebhookForwarder
├── config/
│   ├── whatsapp.php           # Core service, auth, and rate limit configuration
│   └── laravel-whatsapp.php   # Package UI & driver settings
├── database/
│   └── migrations/            # WhatsApp sessions, messages, and settings tables
├── docker-compose.yml         # Local & production compose setup
├── Dockerfile                 # Multi-stage production container with Chromium
├── resources/views/           # Welcome landing & Livewire templates
├── routes/
│   └── web.php                # Core endpoints & n8n routes
├── scripts/
│   ├── start-container.sh     # Container entrypoint & session cleaner
│   └── docker-cleanup.sh      # Maintenance script
└── whatsapp-sidecar/          # Node.js whatsapp-web.js daemon (Puppeteer)
```

---

## 🚀 Quick Start (Local Development)

### 1. Requirements

- PHP 8.3 or 8.4
- Composer 2+
- Node.js 20+
- SQLite3
- Chromium / Google Chrome (for WhatsApp Web QR scan)

### 2. Setup

```bash
# Clone the repository
git clone <your-repo-url> laravel-whatsapp-service
cd laravel-whatsapp-service

# Setup environment variables
cp .env.example .env

# Configure Basic Auth credentials in .env:
# WHATSAPP_BASIC_AUTH_USER=admin
# WHATSAPP_BASIC_AUTH_PASSWORD=your_secret_password
# N8N_BASIC_AUTH_USER=n8n_user
# N8N_BASIC_AUTH_PASSWORD=your_n8n_password

# Install dependencies
composer install
npm install

# Setup application key & database
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Install WhatsApp sidecar dependencies
cd whatsapp-sidecar && npm install && cd ..

# Build frontend assets
npm run build
```

### 3. Run Development Server

```bash
# Run server, queue worker, and log stream simultaneously:
composer run dev

# Or run with standard artisan:
php artisan serve --port=3001
```

Access the application:
- **Landing & Status**: `http://localhost:3001`
- **WhatsApp Web Dashboard**: `http://localhost:3001/whatsapp` (Scan QR code here)
- **n8n Webhook Config**: `http://localhost:3001/integrations/n8n`
- **Health Check**: `http://localhost:3001/health`

---

## 🐳 Docker Deployment

The included `Dockerfile` bundles PHP 8.4 CLI, Node.js 20, Chromium, and SQLite with automated lock cleanup on startup.

```bash
# Build and run with Docker Compose
docker compose up -d --build

# Inspect logs
docker compose logs -f app
```

---

## 📡 API Reference

All requests require **HTTP Basic Authentication** matching your configured `.env` credentials.

### 1. Send Message
- **Endpoint**: `POST /send-message`
- **Auth**: `WHATSAPP_BASIC_AUTH_USER` / `WHATSAPP_BASIC_AUTH_PASSWORD`
- **Payload**:
  ```json
  {
    "to": "6281234567890",
    "message": "Hello from Laravel WhatsApp Service!"
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "session_used": "main",
    "response": { "ack": 1 }
  }
  ```

### 2. WhatsApp Status
- **Endpoint**: `GET /whatsapp-status`
- **Auth**: `WHATSAPP_BASIC_AUTH_USER` / `WHATSAPP_BASIC_AUTH_PASSWORD`
- **Response**:
  ```json
  {
    "status": "success",
    "is_ready": true,
    "phone_number": "6281234567890",
    "push_name": "Admin",
    "driver": "web"
  }
  ```

### 3. n8n Automation Send
- **Endpoint**: `POST /api/n8n/send`
- **Auth**: `N8N_BASIC_AUTH_USER` / `N8N_BASIC_AUTH_PASSWORD`
- **Payload**:
  ```json
  {
    "to": "6281234567890",
    "message": "Automated message from n8n workflow"
  }
  ```

### 4. Health Check
- **Endpoint**: `GET /health`
- **Auth**: Public (Rate-limited)
- **Response**:
  ```json
  {
    "status": "ok",
    "service": "whatsapp-service",
    "sidecar": "running",
    "database": "connected"
  }
  ```

---

## 🔄 Outgoing n8n Webhook Forwarding

When an incoming message arrives on your WhatsApp account, the service forwards a structured payload to your configured `N8N_WEBHOOK_URL`:

```json
{
  "event": "message_received",
  "session_id": "main",
  "from": "628987654321@c.us",
  "phone_number": "628987654321",
  "body": "Hi, I need assistance",
  "timestamp": 1729515000,
  "source": "web_event"
}
```

---

## 📄 License

Open-source software licensed under the [MIT License](LICENSE).
