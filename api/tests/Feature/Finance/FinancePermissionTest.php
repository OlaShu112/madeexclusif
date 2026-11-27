<?php

namespace Tests\Feature\Finance;

use App\Models\User;

class FinancePermissionTest extends FinanceTestCase
{
    public function test_admin_without_permission_is_denied(): void
    {
        $admin = $this->createAdmin();

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_authorised_admin_can_access_dashboard(): void
    {
        $admin = $this->createAdmin();

        $this->grantRoutePermission(
            $admin,
            'admin.finance.overview'
        );

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'currency',
                    'livemode',
                    'revenue',
                    'expenses',
                    'summary',
                ],
            ]);
    }

    public function test_expired_permission_is_denied(): void
    {
        $admin = $this->createAdmin();

        $permission = config(
            'finance.route_permissions.admin.finance.overview'
        );

        $mapping = config(
            'finance.route_permissions',
            []
        );

        $permission = $mapping[
            'admin.finance.overview'
        ];

        $this->grantPermission(
            $admin,
            $permission,
            [
                'expires_at' => now()->subDay(),
            ]
        );

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_future_expiry_permission_is_valid(): void
    {
        $admin = $this->createAdmin();

        $mapping = config(
            'finance.route_permissions',
            []
        );

        $this->grantPermission(
            $admin,
            $mapping['admin.finance.overview'],
            [
                'expires_at' => now()->addDay(),
            ]
        );

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertOk();
    }

    public function test_revoked_permission_denies_access(): void
    {
        $admin = $this->createAdmin();

        $this->grantRoutePermission(
            $admin,
            'admin.finance.overview'
        );

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertOk();

        \App\Models\PlatformAdminPermission::query()
            ->where('user_id', $admin->id)
            ->delete();

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_unconfigured_route_permission_is_denied(): void
    {
        $admin = $this->createFinanceAdmin();

        $mapping = config(
            'finance.route_permissions',
            []
        );

        unset(
            $mapping['admin.finance.overview']
        );

        config([
            'finance.route_permissions' => $mapping,
        ]);

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_non_admin_with_permission_is_denied(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(
            $user->isAdmin()
        );

        $this->grantRoutePermission(
            $user,
            'admin.finance.overview'
        );

        $this->authenticate($user);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_finance_admin_can_access_read_endpoints(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Disable Rate Limiting for This Permission Test
        |--------------------------------------------------------------------------
        |
        | This test sends multiple requests to Finance read endpoints.
        | Disable throttling only for this test while preserving
        | authentication and Finance permission enforcement.
        |
        */

        $this->withoutMiddleware([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        ]);

        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $paths = [
            'overview',
            'trends',
            'transactions',
            'invoices',
            'payments',
            'expenses',
            'expense-categories',
            'credit-notes',
            'tax-rates',
            'reports',
            'reconciliation',
            'reconciliation/unreconciled',
            'reconciliation/audit',
        ];

        foreach ($paths as $path) {

            $this->getJson(
                $this->financeUrl($path)
            )->assertOk();
        }
    }
}