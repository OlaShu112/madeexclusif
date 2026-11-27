<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformExpense extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'approved',
        'paid',
        'rejected',
        'cancelled',
    ];

    public const RECURRING_INTERVALS = [
        'weekly',
        'monthly',
        'quarterly',
        'annual',
    ];

    protected $fillable = [

        'platform_expense_category_id',

        'created_by',

        'approved_by',

        'reference',

        'title',

        'supplier',

        'description',

        'subtotal_minor',

        'tax_minor',

        'total_minor',

        'currency',

        'livemode',

        'status',

        'payment_method',

        'receipt_path',

        'is_recurring',

        'recurring_interval',

        'expense_date',

        'approved_at',

        'paid_at',

        'notes',
    ];

    protected $hidden = [
        'receipt_path',
    ];

    protected $attributes = [

        'currency' => 'gbp',

        'livemode' => false,

        'status' => 'pending',

        'tax_minor' => 0,

        'is_recurring' => false,
    ];

    protected function casts(): array
    {
        return [

            'subtotal_minor' => 'integer',

            'tax_minor' => 'integer',

            'total_minor' => 'integer',

            'livemode' => 'boolean',

            'is_recurring' => 'boolean',

            'expense_date' => 'date',

            'approved_at' => 'datetime',

            'paid_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            PlatformExpenseCategory::class,
            'platform_expense_category_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePaid(
        Builder $query
    ): Builder {

        return $query->where(
            'status',
            'paid'
        );
    }

    public function scopePending(
        Builder $query
    ): Builder {

        return $query->where(
            'status',
            'pending'
        );
    }

    public function scopeApproved(
        Builder $query
    ): Builder {

        return $query->where(
            'status',
            'approved'
        );
    }

    public function scopeRecurring(
        Builder $query
    ): Builder {

        return $query->where(
            'is_recurring',
            true
        );
    }

    public function scopeForCurrency(
        Builder $query,
        string $currency
    ): Builder {

        return $query->where(
            'currency',
            strtolower($currency)
        );
    }

    public function scopeLive(
        Builder $query
    ): Builder {

        return $query->where(
            'livemode',
            true
        );
    }

    public function scopeTestMode(
        Builder $query
    ): Builder {

        return $query->where(
            'livemode',
            false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isLive(): bool
    {
        return $this->livemode === true;
    }

    public function isEditable(): bool
    {
        return $this->status === 'pending';
    }
}