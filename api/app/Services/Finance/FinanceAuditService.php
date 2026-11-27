<?php

namespace App\Services\Finance;

use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformExpense;
use App\Models\PlatformCreditNote;

use App\Services\AuditService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinanceAuditService
{
    public function __construct(
        private AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Allowed Financial Attributes
    |--------------------------------------------------------------------------
    */

    private const FIELDS = [

        'id',

        'store_id',

        'store_subscription_id',

        'platform_invoice_id',

        'platform_expense_category_id',

        'invoice_number',

        'credit_note_number',

        'reference',

        'type',

        'status',

        'currency',

        'livemode',

        'subtotal_minor',

        'discount_minor',

        'tax_minor',

        'total_minor',

        'amount_minor',

        'amount_paid_minor',

        'amount_due_minor',

        'quantity',

        'unit_price_minor',

        'tax_percentage',

        'created_by',

        'approved_by',

        'payment_method',

        'is_recurring',

        'recurring_interval',

        'expense_date',

        'issued_at',

        'due_at',

        'paid_at',

        'approved_at',

        'voided_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Resolve Financial Resource
    |--------------------------------------------------------------------------
    */

    public function entityType(
        Request $request
    ): ?string {

        $route = $request->route()?->getName()
            ?? '';

        return match (true) {

            str_starts_with(
                $route,
                'admin.finance.invoices.'
            ) => 'platform_invoice',

            str_starts_with(
                $route,
                'admin.finance.expenses.'
            ) => 'platform_expense',

            str_starts_with(
                $route,
                'admin.finance.credit-notes.'
            ) => 'platform_credit_note',

            default => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Capture Financial State
    |--------------------------------------------------------------------------
    */

    public function snapshot(
        ?string $entityType,
        ?int $id,
        bool $lock = false
    ): ?array {

        if (!$entityType || !$id) {
            return null;
        }

        $model = match ($entityType) {

            'platform_invoice'
                => PlatformInvoice::class,

            'platform_expense'
                => PlatformExpense::class,

            'platform_credit_note'
                => PlatformCreditNote::class,

            default => null,
        };

        if ($model === null) {
            return null;
        }

        $query = $model::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $record = $query->find($id);

        if (!$record) {
            return null;
        }

        $snapshot = $this->safeAttributes($record);

        if ($entityType === 'platform_invoice') {

            $snapshot['items'] =
                PlatformInvoiceItem::query()
                    ->where(
                        'platform_invoice_id',
                        $record->id
                    )
                    ->orderBy('id')
                    ->get()
                    ->map(
                        fn ($item) =>
                            $this->safeAttributes($item)
                    )
                    ->all();
        }

        if ($entityType === 'platform_credit_note') {

            $invoice = $record->invoice()
                ->first();

            if ($invoice) {

                $snapshot['livemode'] =
                    (bool) $invoice->livemode;

                $snapshot['store_id'] =
                    $invoice->store_id;

                $snapshot['invoice'] =
                    $this->safeAttributes($invoice);
            }
        }

        return $snapshot;
    }

    /*
    |--------------------------------------------------------------------------
    | Record Financial Operation
    |--------------------------------------------------------------------------
    */

    public function record(

        Request $request,

        ?string $entityType,

        ?int $entityId,

        ?array $before,

        ?array $after,

        string $outcome,

        int $statusCode

    ): void {

        $routeName =
            $request->route()?->getName()
            ?? 'admin.finance.unknown';

        $requestId =
            $request->attributes->get(
                'finance_request_id'
            );

        if (!$requestId) {

            $requestId = (string) Str::uuid();

            $request->attributes->set(
                'finance_request_id',
                $requestId
            );
        }

        $storeId = $after['store_id']
            ?? $before['store_id']
            ?? null;

        $this->audit->record(

            action: str_replace(
                'admin.finance.',
                'finance.',
                $routeName
            ),

            storeId: $storeId,

            userId: $request->user()?->id,

            entityType: $entityType,

            entityId: $entityId,

            metadata: [

                'request_id' => $requestId,

                'outcome' => $outcome,

                'http_status' => $statusCode,

                'method' => $request->method(),

                'livemode' => $after['livemode']
                    ?? $before['livemode']
                    ?? null,

                'previous_status' =>
                    $before['status'] ?? null,

                'new_status' =>
                    $after['status'] ?? null,

                'before' => $before,

                'after' => $after,

                'ip_address' => $request->ip(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Safe Attribute Extraction
    |--------------------------------------------------------------------------
    */

    private function safeAttributes(
        Model $record
    ): array {

        $attributes = [];

        foreach (self::FIELDS as $field) {

            if (
                array_key_exists(
                    $field,
                    $record->getAttributes()
                )
            ) {

                $value = $record->getAttribute(
                    $field
                );

                if (
                    $value instanceof
                        \DateTimeInterface
                ) {

                    $value = $value->format(
                        DATE_ATOM
                    );
                }

                if (
                    is_scalar($value)
                    || $value === null
                ) {

                    $attributes[$field] = $value;
                }
            }
        }

        return $attributes;
    }
}