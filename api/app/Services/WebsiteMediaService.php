<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WebsiteMediaService
{
    public function upload(
        Store $store,
        UploadedFile $file,
        string $collection = 'general',
        ?string $altText = null
    ): StoreMedia
    {
        if (!in_array(
            $collection,
            StoreMedia::COLLECTIONS,
            true
        )) {
            throw ValidationException::withMessages([
                'collection' => 'Invalid media collection.',
            ]);
        }

        $dimensions = @getimagesize(
            $file->getRealPath()
        );

        if ($dimensions === false) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded file is not a valid image.',
            ]);
        }

        $mimeType = $file->getMimeType();

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            throw ValidationException::withMessages([
                'image' => 'Unsupported image format.',
            ]);
        }

        $filename = Str::uuid()
            . '.'
            . $extensions[$mimeType];

        $directory = sprintf(
            'stores/%d/media/%s',
            $store->id,
            $collection
        );

        $path = $file->storeAs(
            $directory,
            $filename,
            'public'
        );

        if (!$path) {
            throw new RuntimeException(
                'Unable to store the uploaded image.'
            );
        }

        try {
            return DB::transaction(function () use (
                $store,
                $file,
                $collection,
                $altText,
                $dimensions,
                $mimeType,
                $path
            ) {
                $lockedStore = Store::query()
                    ->lockForUpdate()
                    ->findOrFail($store->id);

                abort_unless(
                    $lockedStore->status === 'active' &&
                    (int) $lockedStore->user_id ===
                        (int) $store->user_id,
                    403,
                    'Store access denied.'
                );

                return $lockedStore->media()->create([
                    'name' => Str::limit(
                        pathinfo(
                            $file->getClientOriginalName(),
                            PATHINFO_FILENAME
                        ),
                        255,
                        ''
                    ),

                    'original_name' => Str::limit(
                        $file->getClientOriginalName(),
                        255,
                        ''
                    ),

                    'disk' => 'public',

                    'path' => $path,

                    'mime_type' => $mimeType,

                    'size' => $file->getSize(),

                    'width' => $dimensions[0],

                    'height' => $dimensions[1],

                    'alt_text' => $altText,

                    'collection' => $collection,
                ]);

            }, 3);

        } catch (Throwable $exception) {

            Storage::disk('public')->delete($path);

            throw $exception;
        }
    }
}