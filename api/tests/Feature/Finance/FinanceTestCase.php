<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Store;
use App\Models\PlatformAdminPermission;
use App\Models\PlatformExpenseCategory;
use App\Models\PlatformTransaction;
use App\Models\PlatformInvoice;
use App\Services\Finance\PlatformTransactionService;
use App\Services\Finance\PlatformInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class FinanceTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Notification::fake();

        Http::preventStrayRequests();

        config([
            'finance.live_operations_enabled' => false,
            'finance.default_livemode' => false,
        ]);
    }

    protected function createAdmin(
        array $attributes = []
    ): User {

        return User::factory()->create(
            array_merge([
                'role' => 'admin',
            ], $attributes)
        );
    }

    protected function authenticate(
        User $user
    ): User {

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    protected function grantPermission(
        User $user,
        string $permission,
        array $attributes = []
    ): PlatformAdminPermission {

        return PlatformAdminPermission::query()
            ->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'permission' => $permission,
                ],
                array_merge([
                    'granted_by' => $user->id,
                    'granted_at' => now(),
                    'expires_at' => null,
                ], $attributes)
            );
    }

    protected function grantRoutePermission(
        User $user,
        string $routeName
    ): void {

        $permissions = config(
            'finance.route_permissions',
            []
        );

        $permission = $permissions[$routeName]
            ?? null;

        $this->assertNotNull(
            $permission,
            "Missing Finance permission for {$routeName}"
        );

        $this->grantPermission(
            $user,
            $permission
        );
    }

    protected function createFinanceAdmin(): User
    {
        $admin = $this->createAdmin();

        foreach (
            config('finance.permissions', [])
            as $permission
        ) {
            $this->grantPermission(
                $admin,
                $permission
            );
        }

        return $admin;
    }

    protected function createStore(
        array $attributes = []
    ): Store {

        $owner = User::factory()->create();

        $identifier = strtolower(
            (string) Str::ulid()
        );

        return Store::query()->create(
            array_merge([
                'user_id' => $owner->id,
                'name' => 'Test Business ' . $identifier,
                'slug' => 'test-business-' . $identifier,
                'type' => 'clothing',
                'status' => 'active',
                'is_main' => false,
            ], $attributes)
        );
    }

    protected function createExpenseCategory(
        array $attributes = []
    ): PlatformExpenseCategory {

        return PlatformExpenseCategory::create(
            array_merge([
                'name' => 'Testing Infrastructure',

                'slug' => 'testing-'
                    . strtolower((string) Str::ulid()),

                'description' =>
                    'Automated testing expense category.',

                'is_active' => true,
            ], $attributes)
        );
    }

    protected function recordTransaction(
        array $attributes = []
    ): PlatformTransaction {

        return app(
            PlatformTransactionService::class
        )->record(
            array_merge([
                'idempotency_key' =>
                    'test-' . (string) Str::ulid(),

                'type' => 'subscription_payment',

                'direction' => 'credit',

                'amount_minor' => 10000,

                'currency' => 'gbp',

                'status' => 'completed',

                'livemode' => false,

                'description' =>
                    'Automated testing transaction.',

                'occurred_at' => now(),
            ], $attributes)
        );
    }

    protected function createDraftInvoice(
        array $attributes = []
    ): PlatformInvoice {

        $store = $this->createStore();

        return app(
            PlatformInvoiceService::class
        )->createDraft(
            array_merge([
                'store_id' => $store->id,
                'type' => 'service',
                'currency' => 'gbp',
                'livemode' => false,
                'due_at' => now()->addDays(14),
            ], $attributes)
        );
    }

    protected function createIssuedInvoice(
        int $amountMinor = 10000
    ): PlatformInvoice {

        $service = app(
            PlatformInvoiceService::class
        );

        $invoice = $this->createDraftInvoice();

        $service->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Test platform service',
                'quantity' => 1,
                'unit_price_minor' => $amountMinor,
            ]
        );

        return $service->issue(
            $invoice->id
        );
    }

    protected function financeAuditCount(): int
    {
        return DB::table('audit_logs')
            ->where('action', 'like', 'finance.%')
            ->count();
    }

    protected function financeUrl(
        string $path = ''
    ): string {

        return '/api/admin/finance'
            . ($path !== ''
                ? '/' . ltrim($path, '/')
                : '');
    }
}