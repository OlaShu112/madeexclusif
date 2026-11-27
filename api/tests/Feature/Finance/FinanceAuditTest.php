<?php

namespace Tests\Feature\Finance;

use App\Models\PlatformInvoice;
use App\Services\AuditService;
use App\Services\Finance\FinanceAuditService;
use App\Http\Middleware\AuditFinanceOperation;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Mockery;

class FinanceAuditTest extends FinanceTestCase
{
    private function auditRequest(
        string $method,
        string $routeName,
        ?int $id = null
    ): Request {

        $admin = $this->createAdmin();

        $uri = $id
            ? '/finance/invoices/' . $id
            : '/finance/invoices';

        $request = Request::create(
            $uri,
            $method
        );

        $request->setUserResolver(
            fn () => $admin
        );

        $route = new Route(
            [$method],
            $id
                ? '/finance/invoices/{id}'
                : '/finance/invoices',
            fn () => null
        );

        $route->name($routeName);

        $route->bind($request);

        $request->setRouteResolver(
            fn () => $route
        );

        $request->attributes->set(
            'finance_request_id',
            (string) Str::uuid()
        );

        return $request;
    }

    public function test_finance_audit_snapshot_contains_financial_state(): void
    {
        $invoice = $this->createIssuedInvoice();

        $snapshot = app(
            FinanceAuditService::class
        )->snapshot(
            'platform_invoice',
            $invoice->id
        );

        $this->assertSame(
            $invoice->id,
            $snapshot['id']
        );

        $this->assertSame(
            'open',
            $snapshot['status']
        );

        $this->assertSame(
            10000,
            $snapshot['total_minor']
        );

        $this->assertArrayHasKey(
            'items',
            $snapshot
        );
    }

    public function test_sensitive_attributes_are_excluded_from_snapshot(): void
    {
        $invoice = $this->createDraftInvoice();

        $invoice->update([
            'billing_details' => [
                'email' => 'private@example.test',
            ],
            'notes' => 'Confidential billing information',
        ]);

        $snapshot = app(
            FinanceAuditService::class
        )->snapshot(
            'platform_invoice',
            $invoice->id
        );

        $this->assertArrayNotHasKey(
            'billing_details',
            $snapshot
        );

        $this->assertArrayNotHasKey(
            'notes',
            $snapshot
        );

        $this->assertArrayNotHasKey(
            'stripe_invoice_id',
            $snapshot
        );
    }

    public function test_successful_operation_creates_audit_log(): void
    {
        $invoice = $this->createDraftInvoice();

        $request = $this->auditRequest(
            'POST',
            'admin.finance.invoices.void',
            $invoice->id
        );

        $response = app(
            AuditFinanceOperation::class
        )->handle(
            $request,
            function () use ($invoice) {

                $invoice->update([
                    'status' => 'void',
                    'voided_at' => now(),
                ]);

                return response()->json([
                    'data' => $invoice->fresh(),
                ]);
            }
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        $this->assertTrue(
            $response->headers->has('X-Request-ID')
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action' => 'finance.invoices.void',
                'entity_type' => 'platform_invoice',
                'entity_id' => $invoice->id,
            ]
        );

        $log = DB::table('audit_logs')
            ->where(
                'action',
                'finance.invoices.void'
            )
            ->latest('id')
            ->first();

        $metadata = json_decode(
            $log->metadata,
            true
        );

        $this->assertSame(
            'success',
            $metadata['outcome']
        );

        $this->assertSame(
            'draft',
            $metadata['previous_status']
        );

        $this->assertSame(
            'void',
            $metadata['new_status']
        );
    }

    public function test_unsuccessful_response_rolls_back_operation(): void
    {
        $invoice = $this->createDraftInvoice();

        $request = $this->auditRequest(
            'POST',
            'admin.finance.invoices.void',
            $invoice->id
        );

        $response = app(
            AuditFinanceOperation::class
        )->handle(
            $request,
            function () use ($invoice) {

                $invoice->update([
                    'status' => 'void',
                ]);

                return response()->json([
                    'message' => 'Invalid operation',
                ], 422);
            }
        );

        $this->assertSame(
            422,
            $response->getStatusCode()
        );

        $this->assertSame(
            'draft',
            $invoice->fresh()->status
        );

        $log = DB::table('audit_logs')
            ->where(
                'action',
                'finance.invoices.void'
            )
            ->latest('id')
            ->first();

        $metadata = json_decode(
            $log->metadata,
            true
        );

        $this->assertSame(
            'failed',
            $metadata['outcome']
        );
    }

    public function test_audit_failure_rolls_back_financial_change(): void
    {
        $invoice = $this->createDraftInvoice();

        $request = $this->auditRequest(
            'POST',
            'admin.finance.invoices.void',
            $invoice->id
        );

        $audit = Mockery::mock(
            AuditService::class
        );

        $audit->shouldReceive('record')
            ->andThrow(
                new RuntimeException(
                    'Simulated audit storage failure'
                )
            );

        $middleware = new AuditFinanceOperation(
            new FinanceAuditService($audit)
        );

        try {

            $middleware->handle(
                $request,
                function () use ($invoice) {

                    $invoice->update([
                        'status' => 'void',
                    ]);

                    return response()->json([
                        'data' => $invoice->fresh(),
                    ]);
                }
            );

            $this->fail(
                'Expected audit failure was not raised.'
            );

        } catch (RuntimeException $exception) {

            $this->assertSame(
                'Simulated audit storage failure',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'draft',
            PlatformInvoice::findOrFail(
                $invoice->id
            )->status
        );
    }
}