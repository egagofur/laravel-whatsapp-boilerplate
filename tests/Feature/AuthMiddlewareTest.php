<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_presets_require_basic_auth(): void
    {
        $this->getJson('/presets')->assertStatus(401);
    }

    public function test_presets_reject_wrong_credentials(): void
    {
        $this->withBasicAuth('wrong', 'creds')
            ->getJson('/presets')
            ->assertStatus(401);
    }

    public function test_presets_accept_valid_credentials(): void
    {
        $this->withBasicAuth('test_wa_user', 'test_wa_pass')
            ->getJson('/presets')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_n8n_requires_basic_auth(): void
    {
        $this->getJson('/api/n8n/status')->assertStatus(401);
    }

    public function test_n8n_accepts_valid_credentials(): void
    {
        $this->withBasicAuth('test_n8n_user', 'test_n8n_pass')
            ->getJson('/api/n8n/status')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_unconfigured_whatsapp_auth_returns_503(): void
    {
        config(['whatsapp.basic_auth.user' => '', 'whatsapp.basic_auth.password' => '']);

        $this->withBasicAuth('any', 'thing')
            ->getJson('/presets')
            ->assertStatus(503)
            ->assertJsonPath('status', 'error');
    }

    public function test_unconfigured_n8n_auth_returns_503(): void
    {
        config(['whatsapp.n8n_auth.user' => '', 'whatsapp.n8n_auth.password' => '']);

        $this->withBasicAuth('any', 'thing')
            ->getJson('/api/n8n/status')
            ->assertStatus(503);
    }
}
