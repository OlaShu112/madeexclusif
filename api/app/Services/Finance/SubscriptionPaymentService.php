<?php

namespace App\Services\Finance;

use App\Models\SubscriptionPayment;
use App\Models\StoreSubscription;
use App\Models\PlatformInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionPaymentService
{
    public function __construct(
        private PlatformTransactionService $transactions
    ) {
    }

    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = SubscriptionPayment::query();

        foreach ([
            'status',
            'store_id',
            'store_subscription_id',
            'provider',
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
                'created_at',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );
        }

        if (!empty($filters['q'])) {
            $query->where(
                'reference',
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

    public function find(int $id): SubscriptionPayment
    {
        return SubscriptionPayment::findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Record Verified Successful Payment
    |--------------------------------------------------------------------------
    |
    | Call only after verifying the payment provider event and
    | resolving the correct subscription and invoice.
    |
    */

    public function recordSuccessfulPayment(
        array $data
    ): SubscriptionPayment {

        $validated = Validator::make($data, [

            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'store_subscription_id' => [
                'required',
                'integer',
                'exists:store_subscriptions,id',
            ],

            'platform_invoice_id' => [
                'nullable',
                'integer',
                'exists:platform_invoices,id',
            ],

            'provider_payment_id' => [
                'required',
                'string',
                'max:191',
            ],

            'stripe_payment_intent_id' => [
                'nullable',
                'string',
                'max:191',
            ],

            'amount_minor' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999',
            ],

            'fee_minor' => [
                'nullable',
                'integer',
                'min:0',
                'lte:amount_minor',
            ],

            'currency' => [
                'required',
                'regex:/^[a-zA-Z]{3}$/',
            ],

            'livemode' => [
                'required',
                'boolean',
            ],

            'payment_method' => [
                'nullable',
                'string',
                'max:100',
            ],

            'paid_at' => [
                'required',
                'date',
            ],

        ])->validate();

        $validated['currency'] = strtolower(
            $validated['currency']
        );

        return DB::transaction(function () use (
            $validated
        ) {

            $subscription = StoreSubscription::query()
                ->lockForUpdate()
                ->findOrFail(
                    $validated['store_subscription_id']
                );

            if (
                (int) $subscription->store_id
                !== (int) $validated['store_id']
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'The subscription does not belong to this business.',
                ]);
            }

            $paymentKey = 'stripe-payment-'
                . hash(
                    'sha256',
                    $validated['provider_payment_id']
                );

            $existing = SubscriptionPayment::query()
                ->where('idempotency_key', $paymentKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {

                if (
                    $existing->amount_minor
                        !== (int) $validated['amount_minor']

                    || $existing->currency
                        !== $validated['currency']

                    || $existing->livemode
                        !== (bool) $validated['livemode']

                    || (int) $existing->store_id
                        !== (int) $validated['store_id']

                    || (int) $existing->store_subscription_id
                        !== (int) $validated['store_subscription_id']

                    || $existing->platform_invoice_id
                        != ($validated['platform_invoice_id'] ?? null)

                    || $existing->provider_payment_id
                        !== $validated['provider_payment_id']
                ) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'The existing payment has conflicting details.',
                    ]);
                }

                if (
                    !in_array(
                        $existing->status,
                        [
                            'succeeded',
                            'partially_refunded',
                            'refunded',
                            'disputed',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'This payment requires reconciliation.',
                    ]);
                }

                return $existing;
            }

            $invoice = null;

            if (!empty($validated['platform_invoice_id'])) {

                $invoice = PlatformInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $validated['platform_invoice_id']
                    );

                $this->validateInvoice(
                    $invoice,
                    $validated
                );
            }

            $payment = SubscriptionPayment::create(
                array_merge($validated, [

                    'reference' => 'PAY-'
                        . now()->format('Ymd')
                        . '-'
                        . strtoupper((string) Str::ulid()),

                    'idempotency_key' => $paymentKey,

                    'provider' => 'stripe',

                    'status' => 'succeeded',

                    'fee_minor' =>
                        (int) ($validated['fee_minor'] ?? 0),

                    'refunded_minor' => 0,
                ])
            );

            $this->transactions->record([

                'store_id' => $payment->store_id,

                'idempotency_key' =>
                    'subscription-payment-' . $payment->id,

                'type' => 'subscription_payment',

                'direction' => 'credit',

                'amount_minor' => $payment->amount_minor,

                'currency' => $payment->currency,

                'status' => 'completed',

                'source_type' => 'subscription_payment',

                'source_id' => $payment->id,

                'provider' => 'stripe',

                'provider_reference' =>
                    $payment->provider_payment_id,

                'livemode' => $payment->livemode,

                'description' =>
                    'Subscription payment: '
                    . $payment->reference,

                'occurred_at' => $payment->paid_at,
            ]);

            if ($payment->fee_minor > 0) {

                $this->transactions->record([

                    'store_id' => $payment->store_id,

                    'idempotency_key' =>
                        'subscription-payment-fee-'
                        . $payment->id,

                    'type' => 'processing_fee',

                    'direction' => 'debit',

                    'amount_minor' => $payment->fee_minor,

                    'currency' => $payment->currency,

                    'status' => 'completed',

                    'source_type' => 'subscription_payment',

                    'source_id' => $payment->id,

                    'provider' => 'stripe',

                    'livemode' => $payment->livemode,

                    'description' =>
                        'Payment processing fee: '
                        . $payment->reference,

                    'occurred_at' => $payment->paid_at,
                ]);
            }

            if ($invoice) {

                $paidAmount =
                    $invoice->amount_paid_minor
                    + $payment->amount_minor;

                $dueAmount = max(
                    0,
                    $invoice->amount_due_minor
                    - $payment->amount_minor
                );

                $invoice->update([

                    'amount_paid_minor' => $paidAmount,

                    'amount_due_minor' => $dueAmount,

                    'status' => $dueAmount === 0
                        ? 'paid'
                        : 'partially_paid',

                    'paid_at' => $dueAmount === 0
                        ? $payment->paid_at
                        : null,
                ]);
            }

            return $payment->refresh();

        }, 3);
    }

    private function validateInvoice(
        PlatformInvoice $invoice,
        array $data
    ): void {

        if (
            (int) $invoice->store_id
                !== (int) $data['store_id']

            || (int) $invoice->store_subscription_id
                !== (int) $data['store_subscription_id']

            || $invoice->currency !== $data['currency']

            || $invoice->livemode
                !== (bool) $data['livemode']

            || !in_array(
                $invoice->status,
                ['open', 'partially_paid', 'overdue'],
                true
            )

            || $data['amount_minor']
                > $invoice->amount_due_minor
        ) {
            throw ValidationException::withMessages([
                'invoice' =>
                    'The payment does not match the outstanding invoice.',
            ]);
        }
    }
}