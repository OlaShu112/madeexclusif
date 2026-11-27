<?php

namespace App\Services\Finance;

use App\Models\PlatformInvoice;
use App\Models\PlatformTaxRate;
use App\Models\StoreSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformInvoiceService
{
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = PlatformInvoice::query();

        foreach ([
            'status',
            'type',
            'store_id',
            'store_subscription_id',
        ] as $field) {

            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $query->where(
            'livemode',
            filter_var(
                $filters['livemode'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
        );

        if (!empty($filters['currency'])) {
            $query->where(
                'currency',
                strtolower($filters['currency'])
            );
        }

        if (!empty($filters['from'])) {
            $query->whereDate(
                'issued_at',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {
            $query->whereDate(
                'issued_at',
                '<=',
                $filters['to']
            );
        }

        if (!empty($filters['q'])) {
            $query->where(
                'invoice_number',
                'like',
                '%' . trim($filters['q']) . '%'
            );
        }

        return $query
            ->latest('id')
            ->paginate(
                max(
                    1,
                    min(
                        (int) ($filters['per_page'] ?? 25),
                        100
                    )
                )
            );
    }

    public function find(int $id): PlatformInvoice
    {
        return PlatformInvoice::query()
            ->with([
                'items',
                'payments',
                'creditNotes',
            ])
            ->findOrFail($id);
    }

    public function createDraft(
        array $data
    ): PlatformInvoice {

        $validated = Validator::make($data, [

            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'store_subscription_id' => [
                'nullable',
                'integer',
                'exists:store_subscriptions,id',
            ],

            'type' => [
                'required',
                Rule::in(PlatformInvoice::TYPES),
            ],

            'currency' => [
                'required',
                'regex:/^[a-zA-Z]{3}$/',
            ],

            'livemode' => [
                'required',
                'boolean',
            ],

            'billing_details' => [
                'nullable',
                'array',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'due_at' => [
                'nullable',
                'date',
            ],

            'period_start' => [
                'nullable',
                'date',
            ],

            'period_end' => [
                'nullable',
                'date',
                'after_or_equal:period_start',
            ],

        ])->validate();

        if (!empty($validated['store_subscription_id'])) {

            $subscription = StoreSubscription::findOrFail(
                $validated['store_subscription_id']
            );

            if (
                (int) $subscription->store_id
                !== (int) $validated['store_id']
            ) {
                throw ValidationException::withMessages([
                    'store_subscription_id' =>
                        'The subscription does not belong to this business.',
                ]);
            }
        }

        return PlatformInvoice::create(
            array_merge($validated, [

                'invoice_number' => 'INV-'
                    . now()->format('Ymd')
                    . '-'
                    . strtoupper((string) Str::ulid()),

                'currency' =>
                    strtolower($validated['currency']),

                'status' => 'draft',

                'subtotal_minor' => 0,

                'discount_minor' => 0,

                'tax_minor' => 0,

                'total_minor' => 0,

                'amount_paid_minor' => 0,

                'amount_due_minor' => 0,
            ])
        );
    }

    public function addItem(
        int $invoiceId,
        array $data
    ): PlatformInvoice {

        $validated = Validator::make($data, [

            'type' => [
                'required',
                Rule::in(PlatformInvoice::TYPES),
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'unit_price_minor' => [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],

            'discount_minor' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'platform_tax_rate_id' => [
                'nullable',
                'integer',
                'exists:platform_tax_rates,id',
            ],

        ])->validate();

        return DB::transaction(function () use (
            $invoiceId,
            $validated
        ) {

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $this->ensureDraft($invoice);

            $subtotal =
                (int) $validated['quantity']
                * (int) $validated['unit_price_minor'];

            $discount =
                (int) ($validated['discount_minor'] ?? 0);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_minor' =>
                        'The discount cannot exceed the item subtotal.',
                ]);
            }

            $taxPercentage = '0.00';

            $taxAmount = 0;

            $taxableAmount = $subtotal - $discount;

            if (!empty($validated['platform_tax_rate_id'])) {

                $rate = PlatformTaxRate::query()
                    ->active()
                    ->findOrFail(
                        $validated['platform_tax_rate_id']
                    );

                $taxPercentage = $rate->percentage;

                $parts = explode(
                    '.',
                    (string) $taxPercentage
                );

                $basisPoints =
                    ((int) $parts[0] * 100)
                    + (int) str_pad(
                        $parts[1] ?? '',
                        2,
                        '0'
                    );

                if ($rate->is_inclusive) {

                    $denominator = 10000 + $basisPoints;

                    $taxAmount = intdiv(
                        ($taxableAmount * $basisPoints)
                        + intdiv($denominator, 2),
                        $denominator
                    );

                } else {

                    $taxAmount = intdiv(
                        ($taxableAmount * $basisPoints)
                        + 5000,
                        10000
                    );
                }

                $total = $rate->is_inclusive
                    ? $taxableAmount
                    : $taxableAmount + $taxAmount;

            } else {

                $total = $taxableAmount;
            }

            $invoice->items()->create([

                'platform_tax_rate_id' =>
                    $validated['platform_tax_rate_id']
                    ?? null,

                'type' => $validated['type'],

                'description' =>
                    $validated['description'],

                'quantity' => $validated['quantity'],

                'unit_price_minor' =>
                    $validated['unit_price_minor'],

                'subtotal_minor' => $subtotal,

                'discount_minor' => $discount,

                'tax_percentage' => $taxPercentage,

                'tax_minor' => $taxAmount,

                'total_minor' => $total,

                'metadata' => [
                    'tax_inclusive' =>
                        isset($rate)
                            ? $rate->is_inclusive
                            : false,
                ],
            ]);

            return $this->recalculate($invoice);

        }, 3);
    }

    public function removeItem(
        int $invoiceId,
        int $itemId
    ): PlatformInvoice {

        return DB::transaction(function () use (
            $invoiceId,
            $itemId
        ) {

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $this->ensureDraft($invoice);

            $invoice->items()
                ->findOrFail($itemId)
                ->delete();

            return $this->recalculate($invoice);

        }, 3);
    }

    public function issue(int $id): PlatformInvoice
    {
        return DB::transaction(function () use ($id) {

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->ensureDraft($invoice);

            if (!$invoice->items()->exists()) {
                throw ValidationException::withMessages([
                    'invoice' =>
                        'An invoice must contain at least one item.',
                ]);
            }

            $invoice = $this->recalculate($invoice);

            if (
                $invoice->due_at
                && $invoice->due_at->lt(now()->startOfDay())
            ) {
                throw ValidationException::withMessages([
                    'due_at' =>
                        'The payment due date cannot be in the past.',
                ]);
            }

            $invoice->update([

                'status' =>
                    $invoice->total_minor === 0
                        ? 'paid'
                        : 'open',

                'issued_at' => now(),

                'due_at' =>
                    $invoice->due_at
                    ?? now()->addDays(14),

                'paid_at' =>
                    $invoice->total_minor === 0
                        ? now()
                        : null,
            ]);

            return $invoice->refresh()->load('items');

        }, 3);
    }

    public function void(int $id): PlatformInvoice
    {
        return DB::transaction(function () use ($id) {

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if (
                !in_array(
                    $invoice->status,
                    ['draft', 'open', 'overdue'],
                    true
                )
                || $invoice->amount_paid_minor > 0
                || $invoice->payments()->exists()
                || $invoice->creditNotes()->exists()
                || $invoice->stripe_invoice_id !== null
            ) {
                throw ValidationException::withMessages([
                    'invoice' =>
                        'This invoice cannot be voided locally.',
                ]);
            }

            $invoice->update([
                'status' => 'void',
                'voided_at' => now(),
                'amount_due_minor' => 0,
            ]);

            return $invoice->refresh();

        }, 3);
    }

    private function recalculate(
        PlatformInvoice $invoice
    ): PlatformInvoice {

        $items = $invoice->items()->get();

        $subtotal = (int) $items->sum('subtotal_minor');

        $discount = (int) $items->sum('discount_minor');

        $tax = (int) $items->sum('tax_minor');

        $total = (int) $items->sum('total_minor');

        $invoice->update([

            'subtotal_minor' => $subtotal,

            'discount_minor' => $discount,

            'tax_minor' => $tax,

            'total_minor' => $total,

            'amount_due_minor' => max(
                0,
                $total - $invoice->amount_paid_minor
            ),
        ]);

        return $invoice->refresh()->load('items');
    }

    private function ensureDraft(
        PlatformInvoice $invoice
    ): void {

        if (
            $invoice->status !== 'draft'
            || $invoice->stripe_invoice_id !== null
        ) {
            throw ValidationException::withMessages([
                'invoice' =>
                    'Only locally managed draft invoices can be modified.',
            ]);
        }
    }
}