<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreUsageRecord extends Model
{
    protected $fillable = [
        'store_id',
        'metric',
        'period_start',
        'quantity',
    ];

    protected $casts = [
        'period_start' => 'date',
        'quantity' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForMetric(
        $query,
        string $metric
    ) {
        return $query->where('metric', $metric);
    }

    public function scopeForPeriod(
        $query,
        string $period
    ) {
        return $query->whereDate(
            'period_start',
            $period
        );
    }
}