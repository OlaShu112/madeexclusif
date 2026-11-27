<?php

namespace App\Services\Finance;

use App\Models\PlatformExpense;
use App\Models\PlatformExpenseCategory;
use App\Models\PlatformTransaction;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformExpenseService
{
    public function __construct(
        private PlatformTransactionService $transactions
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Categories
    |--------------------------------------------------------------------------
    */

    public function categories()
    {
        return PlatformExpenseCategory::query()
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = PlatformExpense::query()
            ->with('category');

        $query->where(
            'livemode',
            filter_var(
                $filters['livemode'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
        );

        if (!empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );
        }

        if (!empty($filters['category_id'])) {

            $query->where(
                'platform_expense_category_id',
                $filters['category_id']
            );
        }

        if (!empty($filters['currency'])) {

            $query->where(
                'currency',
                strtolower($filters['currency'])
            );
        }

        if (!empty($filters['from'])) {

            $query->whereDate(
                'expense_date',
                '>=',
                $filters['from']
            );
        }

        if (!empty($filters['to'])) {

            $query->whereDate(
                'expense_date',
                '<=',
                $filters['to']
            );
        }

        if (!empty($filters['q'])) {

            $search = trim($filters['q']);

            $query->where(
                function ($query) use ($search) {

                    $query->where(
                        'title',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'supplier',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'reference',
                        'like',
                        "%{$search}%"
                    );
                }
            );
        }

        return $query
            ->latest('expense_date')
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

    /*
    |--------------------------------------------------------------------------
    | Find Expense
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id,
        bool $livemode = false
    ): PlatformExpense {

        return PlatformExpense::query()
            ->with('category')
            ->where('livemode', $livemode)
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Expense
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        int $userId
    ): PlatformExpense {

        $validated = $this->validateExpense($data);

        $livemode = filter_var(
            $data['livemode'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return PlatformExpense::create(
            array_merge($validated, [

                'created_by' => $userId,

                'reference' => 'EXP-'
                    . now()->format('Ymd')
                    . '-'
                    . strtoupper((string) Str::ulid()),

                'livemode' => $livemode,

                'status' => 'pending',

                'total_minor' =>
                    (int) $validated['subtotal_minor']
                    + (int) $validated['tax_minor'],
            ])
        )->load('category');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Expense
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data,
        bool $livemode = false
    ): PlatformExpense {

        $validated = $this->validateExpense($data);

        return DB::transaction(
            function () use (
                $id,
                $validated,
                $livemode
            ) {

                $expense = $this->lockExpense(
                    $id,
                    $livemode
                );

                $this->ensureStatus(
                    $expense,
                    ['pending']
                );

                $expense->update(
                    array_merge($validated, [

                        'total_minor' =>
                            (int) $validated['subtotal_minor']
                            + (int) $validated['tax_minor'],
                    ])
                );

                return $expense
                    ->refresh()
                    ->load('category');

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Expense
    |--------------------------------------------------------------------------
    */

    public function approve(
        int $id,
        int $userId,
        bool $livemode = false
    ): PlatformExpense {

        return DB::transaction(
            function () use (
                $id,
                $userId,
                $livemode
            ) {

                $expense = $this->lockExpense(
                    $id,
                    $livemode
                );

                $this->ensureStatus(
                    $expense,
                    ['pending']
                );

                $expense->update([

                    'status' => 'approved',

                    'approved_by' => $userId,

                    'approved_at' => now(),
                ]);

                return $expense->refresh();

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Expense
    |--------------------------------------------------------------------------
    */

    public function reject(
        int $id,
        string $reason,
        bool $livemode = false
    ): PlatformExpense {

        Validator::make(
            ['reason' => $reason],
            [
                'reason' => [
                    'required',
                    'string',
                    'max:2000',
                ],
            ]
        )->validate();

        return DB::transaction(
            function () use (
                $id,
                $reason,
                $livemode
            ) {

                $expense = $this->lockExpense(
                    $id,
                    $livemode
                );

                $this->ensureStatus(
                    $expense,
                    ['pending']
                );

                $expense->update([

                    'status' => 'rejected',

                    'notes' => $this->appendNote(
                        $expense->notes,
                        'Rejection reason: ' . $reason
                    ),
                ]);

                return $expense->refresh();

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Record Expense Payment
    |--------------------------------------------------------------------------
    */

    public function markAsPaid(
        int $id,
        int $userId,
        string $paymentMethod,
        bool $livemode = false
    ): PlatformExpense {

        Validator::make(
            [
                'payment_method' => $paymentMethod,
            ],
            [
                'payment_method' => [
                    'required',
                    Rule::in([
                        'bank_transfer',
                        'card',
                        'direct_debit',
                        'cash',
                        'paypal',
                        'other',
                    ]),
                ],
            ]
        )->validate();

        return DB::transaction(
            function () use (
                $id,
                $userId,
                $paymentMethod,
                $livemode
            ) {

                $expense = $this->lockExpense(
                    $id,
                    $livemode
                );

                if ($expense->status === 'paid') {

                    $transaction =
                        PlatformTransaction::query()
                            ->where(
                                'idempotency_key',
                                'platform-expense-paid-'
                                . $expense->id
                            )
                            ->firstOrFail();

                    if (
                        $transaction->livemode
                            !== $expense->livemode

                        || $expense->payment_method
                            !== $paymentMethod

                        || $transaction->amount_minor
                            !== $expense->total_minor

                        || $transaction->currency
                            !== $expense->currency

                        || $transaction->status
                            !== 'completed'

                        || $transaction->direction
                            !== 'debit'

                        || $transaction->type
                            !== 'expense'
                    ) {

                        throw ValidationException::withMessages([
                            'expense' =>
                                'The existing expense payment requires reconciliation.',
                        ]);
                    }

                    return $expense;
                }

                $this->ensureStatus(
                    $expense,
                    ['approved']
                );

                $paidAt = now();

                $expense->update([

                    'status' => 'paid',

                    'payment_method' => $paymentMethod,

                    'paid_at' => $paidAt,
                ]);

                $this->transactions->record([

                    'created_by' => $userId,

                    'idempotency_key' =>
                        'platform-expense-paid-'
                        . $expense->id,

                    'type' => 'expense',

                    'direction' => 'debit',

                    'amount_minor' =>
                        $expense->total_minor,

                    'currency' => $expense->currency,

                    'status' => 'completed',

                    'source_type' => 'platform_expense',

                    'source_id' => $expense->id,

                    'provider' => 'manual',

                    'livemode' => $expense->livemode,

                    'description' =>
                        'Expense payment: '
                        . $expense->title,

                    'occurred_at' => $paidAt,
                ]);

                return $expense->refresh();

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Expense
    |--------------------------------------------------------------------------
    */

    public function cancel(
        int $id,
        bool $livemode = false
    ): PlatformExpense {

        return DB::transaction(
            function () use ($id, $livemode) {

                $expense = $this->lockExpense(
                    $id,
                    $livemode
                );

                $this->ensureStatus(
                    $expense,
                    ['pending', 'approved']
                );

                $expense->update([
                    'status' => 'cancelled',
                ]);

                return $expense->refresh();

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Summary
    |--------------------------------------------------------------------------
    */

    public function summary(
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $query = PlatformExpense::query()
            ->where(
                'currency',
                strtolower($currency)
            )
            ->where('livemode', $livemode);

        return [

            'currency' => strtoupper($currency),

            'livemode' => $livemode,

            'total_expenses' =>
                (clone $query)->count(),

            'pending_expenses' =>
                (clone $query)->pending()->count(),

            'approved_expenses' =>
                (clone $query)->approved()->count(),

            'paid_expenses' =>
                (clone $query)->paid()->count(),

            'pending_amount_minor' =>
                (int) (clone $query)
                    ->pending()
                    ->sum('total_minor'),

            'approved_amount_minor' =>
                (int) (clone $query)
                    ->approved()
                    ->sum('total_minor'),

            'paid_amount_minor' =>
                (int) (clone $query)
                    ->paid()
                    ->sum('total_minor'),

            'recurring_expenses' =>
                (clone $query)
                    ->recurring()
                    ->whereIn(
                        'status',
                        ['pending', 'approved', 'paid']
                    )
                    ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateExpense(
        array $data
    ): array {

        $validated = Validator::make($data, [

            'platform_expense_category_id' => [
                'required',
                'integer',
                Rule::exists(
                    'platform_expense_categories',
                    'id'
                )->where('is_active', true),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'supplier' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'subtotal_minor' => [
                'required',
                'integer',
                'min:1',
                'max:499999999999',
            ],

            'tax_minor' => [
                'nullable',
                'integer',
                'min:0',
                'max:499999999999',
            ],

            'currency' => [
                'required',
                'string',
                'regex:/^[a-zA-Z]{3}$/',
            ],

            'is_recurring' => [
                'required',
                'boolean',
            ],

            'recurring_interval' => [
                'nullable',
                'required_if:is_recurring,true',
                Rule::in(
                    PlatformExpense::RECURRING_INTERVALS
                ),
            ],

            'expense_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

        ])->validate();

        $validated['currency'] = strtolower(
            $validated['currency']
        );

        $validated['tax_minor'] =
            (int) ($validated['tax_minor'] ?? 0);

        if (!$validated['is_recurring']) {

            $validated['recurring_interval'] = null;
        }

        return $validated;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    private function lockExpense(
        int $id,
        bool $livemode
    ): PlatformExpense {

        return PlatformExpense::query()
            ->where('livemode', $livemode)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    private function ensureStatus(
        PlatformExpense $expense,
        array $allowedStatuses
    ): void {

        if (
            !in_array(
                $expense->status,
                $allowedStatuses,
                true
            )
        ) {

            throw ValidationException::withMessages([
                'status' =>
                    'This action is not permitted for the current expense status.',
            ]);
        }
    }

    private function appendNote(
        ?string $existing,
        string $note
    ): string {

        return trim(
            ($existing ?? '')
            . "\n"
            . $note
        );
    }
}