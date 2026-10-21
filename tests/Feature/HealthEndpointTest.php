<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'checks' => ['app', 'database', 'whatsapp_driver'],
                'timestamp',
            ]);
    }

    public function test_laravel_up_endpoint_still_works(): void
    {
        $this->get('/up')->assertOk();
    }
}
