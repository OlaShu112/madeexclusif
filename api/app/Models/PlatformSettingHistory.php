<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSettingHistory extends Model
{
    protected $fillable = [
        'user_id',
        'section',
        'setting_key',
        'previous_value',
        'new_value',
        'action',
    ];

    protected $casts = [
        'previous_value' => 'array',
        'new_value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}