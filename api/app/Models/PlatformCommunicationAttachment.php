<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommunicationAttachment extends Model
{
    protected $fillable = [
        'platform_communication_id',
        'name',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $hidden = [
        'disk',
        'path',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(
            PlatformCommunication::class,
            'platform_communication_id'
        );
    }

    public function sizeInKilobytes(): float
    {
        return round($this->size / 1024, 2);
    }

    public function sizeInMegabytes(): float
    {
        return round(
            $this->size / (1024 * 1024),
            2
        );
    }
}