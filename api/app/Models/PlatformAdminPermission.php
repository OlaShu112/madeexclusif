<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAdminPermission extends Model
{
    protected $fillable = [

        'user_id',

        'permission',

        'granted_by',

        'granted_at',

        'expires_at',
    ];

    protected function casts(): array
    {
        return [

            'granted_at' => 'datetime',

            'expires_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'granted_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(
            function (Builder $query) {

                $query->whereNull('expires_at')
                    ->orWhere(
                        'expires_at',
                        '>',
                        now()
                    );
            }
        );
    }

    public function scopeForUser(
        Builder $query,
        int $userId
    ): Builder {

        return $query->where(
            'user_id',
            $userId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->lte(now());
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}