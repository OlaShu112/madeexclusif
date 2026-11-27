<?php

namespace App\Services\Finance;

use App\Models\PlatformTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformTransactionService
{
    public function record(array $data): PlatformTransaction
    {
        $validated = Validator::make($data, [
            'store_id' => [
                'nullable',
                'integer',
                'exists:stores,id',
            ],

            'created_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:191',
            ],

            'type' => [
                'required',
                Rule::in(PlatformTransaction::TYPES),
            ],

            'direction' => [
                'required',
                Rule::in(PlatformTransaction::DIRECTIONS),
            ],

            'amount_minor' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999',
            ],

            'currency' => [
                'required',
                'string',
                'regex:/^[a-zA-Z]{3}$/',
            ],

            'status' => [
                'required',
                Rule::in(PlatformTransaction::STATUSES),
            ],

            'source_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'source_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'provider' => [
                'nullable',
                'string',
                'max:100',
            ],

            'provider_reference' => [
                'nullable',
                'string',
                'max:191',
            ],

            'livemode' => [
                'required',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],

            'occurred_at' => [
                'required',
                'date',
            ],
        ])->validate();

        $validated['currency'] = strtolower(
            $validated['currency']
        );

        $this->validateDirection(
            $validated['type'],
            $validated['direction']
        );

        return DB::transaction(function () use ($validated) {

            $transaction = PlatformTransaction::firstOrCreate(
                [
                    'idempotency_key' =>
                        $validated['idempotency_key'],
                ],
                array_merge($validated, [
                    'reference' => $this->generateReference(),
                ])
            );

            if (
                $transaction->type !== $validated['type']
                || $transaction->direction !== $validated['direction']
                || $transaction->amount_minor !==
                    (int) $validated['amount_minor']
                || $transaction->currency !== $validated['currency']
                || $transaction->livemode !==
                    (bool) $validated['livemode']
                || $transaction->store_id !=
                    ($validated['store_id'] ?? null)
                || $transaction->source_type !==
                    ($validated['source_type'] ?? null)
                || $transaction->source_id !=
                    ($validated['source_id'] ?? null)
                || $transaction->provider !==
                    ($validated['provider'] ?? null)
                || $transaction->provider_reference !==
                    ($validated['provider_reference'] ?? null)
                || $transaction->status !== $validated['status']
            ) {
                throw ValidationException::withMessages([
                    'idempotency_key' =>
                        'This key belongs to a different transaction.',
                ]);
            }

            return $transaction;
        }, 3);
    }

    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = PlatformTransaction::query();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['direction'])) {
            $query->where(
                'direction',
                $filters['direction']
            );
        }

        if (!empty($filters['currency'])) {
            $query->where(
                'currency',
                strtolower($filters['currency'])
            );
        }

        if (!empty($filters['store_id'])) {
            $query->where(
                'store_id',
                $filters['store_id']
            );
        }

        $query->where(
            'livemode',
            (bool) ($filters['livemode'] ?? false)
        );

        if (!empty($filters['from'])) {
            $query->whereDate(
                'occurred_at',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {
            $query->whereDate(
                'occurred_at',
                '<=',
                $filters['to']
            );
        }

        if (isset($filters['reconciled'])) {

            if ($filters['reconciled']) {
                $query->whereNotNull('reconciled_at');
            } else {
                $query->whereNull('reconciled_at');
            }
        }

        if (!empty($filters['q'])) {

            $search = trim($filters['q']);

            $query->where(function ($query) use ($search) {

                $query->where(
                    'reference',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $perPage = max(
            1,
            min((int) ($filters['per_page'] ?? 25), 100)
        );

        return $query
            ->latest('occurred_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): PlatformTransaction
    {
        return PlatformTransaction::findOrFail($id);
    }

    private function validateDirection(
        string $type,
        string $direction
    ): void {

        $creditTypes = [
            'subscription_payment',
            'commission',
            'service_payment',
            'usage_payment',
            'expense_reversal',
            'other_income',
        ];

        $debitTypes = [
            'subscription_refund',
            'commission_reversal',
            'processing_fee',
            'expense',
        ];

        if (
            in_array($type, $creditTypes, true)
            && $direction !== 'credit'
        ) {
            throw ValidationException::withMessages([
                'direction' =>
                    'This transaction type requires a credit.',
            ]);
        }

        if (
            in_array($type, $debitTypes, true)
            && $direction !== 'debit'
        ) {
            throw ValidationException::withMessages([
                'direction' =>
                    'This transaction type requires a debit.',
            ]);
        }
    }

    private function generateReference(): string
    {
        return 'TXN-'
            . now()->format('Ymd')
            . '-'
            . strtoupper((string) Str::ulid());
    }
}