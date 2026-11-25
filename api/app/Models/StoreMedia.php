<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StoreMedia extends Model
{
    protected $table = 'store_media';

    public const COLLECTIONS = [
        'logo',
        'hero',
        'promotion',
        'category',
        'general',
    ];

    protected $fillable = [
        'store_id',
        'name',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'collection',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)
            ->url($this->path);
    }
}