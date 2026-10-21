<?php

namespace App\Providers;

use App\Services\N8nWebhookForwarder;
use App\Services\WhatsApp\CloudWhatsAppSender;
use App\Services\WhatsApp\WebWhatsAppSender;
use App\Services\WhatsApp\WhatsAppSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppSender::class, function () {
            $driver = config('whatsapp.whatsapp_driver', 'web');

            return match ($driver) {
                'cloud' => new CloudWhatsAppSender,
                default => new WebWhatsAppSender,
            };
        });

        $this->app->singleton(N8nWebhookForwarder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerN8nWebhookForwarders();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $perMinute = (int) config('whatsapp.rate_limits.api', 60);

            return Limit::perMinute($perMinute)->by($request->getUser() ?: $request->ip());
        });

        RateLimiter::for('send', function (Request $request) {
            $perMinute = (int) config('whatsapp.rate_limits.send', 20);

            return Limit::perMinute($perMinute)->by($request->getUser() ?: $request->ip());
        });

        RateLimiter::for('n8n', function (Request $request) {
            $perMinute = (int) config('whatsapp.rate_limits.n8n', 60);

            return Limit::perMinute($perMinute)->by($request->getUser() ?: $request->ip());
        });

        RateLimiter::for('health', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    private function registerN8nWebhookForwarders(): void
    {
        Event::listen(
            \Kstmostofa\LaravelWhatsApp\Events\Web\MessageReceived::class,
            function (\Kstmostofa\LaravelWhatsApp\Events\Web\MessageReceived $event) {
                /** @var N8nWebhookForwarder $forwarder */
                $forwarder = app(N8nWebhookForwarder::class);

                $source = isset($event->payload['message']) ? 'web_event' : 'web_event';
                $message = $event->message();

                if ($message === []) {
                    Log::warning('n8n forward skipped: empty message payload', [
                        'session_id' => $event->sessionId,
                        'payload_keys' => array_keys($event->payload),
                    ]);

                    return;
                }

                $forwarder->forwardMessage($event->sessionId, $message, $source);
            }
        );

        Event::listen(
            \Kstmostofa\LaravelWhatsApp\Events\Web\SessionReady::class,
            function (\Kstmostofa\LaravelWhatsApp\Events\Web\SessionReady $event) {
                app(N8nWebhookForwarder::class)->forwardSessionReady($event->sessionId);
            }
        );
    }
}
