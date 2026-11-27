<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Services\System\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_system_health(): void
    {
        $this->getJson('/api/admin/system/health')
            ->assertUnauthorized();
    }

    public function test_customer_cannot_access_system_health(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/system/health')
            ->assertForbidden();
    }

    public function test_administrator_can_access_system_health(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $this->mock(
            SystemHealthService::class,
            function ($mock) {
                $mock->shouldReceive('check')
                    ->once()
                    ->andReturn([
                        'healthy' => true,
                        'status' => 'healthy',
                        'checks' => [],
                        'checked_at' => now()->toIso8601String(),
                    ]);
            }
        );

        $this->getJson('/api/admin/system/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'healthy');
    }

    public function test_unhealthy_system_returns_503(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $this->mock(
            SystemHealthService::class,
            function ($mock) {
                $mock->shouldReceive('check')
                    ->once()
                    ->andReturn([
                        'healthy' => false,
                        'status' => 'unhealthy',
                        'checks' => [],
                        'checked_at' => now()->toIso8601String(),
                    ]);
            }
        );

        $this->getJson('/api/admin/system/health')
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}