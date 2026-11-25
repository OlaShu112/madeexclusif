<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    protected $fillable = [
        'store_id',
        'domain',
        'verification_token',
        'status',
        'ssl_status',
        'is_primary',
        'verified_at',
        'last_checked_at',
        'ssl_expires_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'verification_token',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
