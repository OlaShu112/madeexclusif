<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'store_id',
        'email',
        'name',
        'status',
        'confirmation_token_hash',
        'unsubscribe_token_hash',
        'consent_version',
        'consented_at',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected $hidden = [
        'confirmation_token_hash',
        'unsubscribe_token_hash',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
