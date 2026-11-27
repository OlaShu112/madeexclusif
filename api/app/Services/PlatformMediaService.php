<?php

namespace App\Services;

use App\Models\PlatformMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlatformMediaService
{
    public const COLLECTIONS = [
        'logo',
        'favicon',
        'login',
        'email',
        'general',
    ];

    public function upload(
        UploadedFile $file,
        string $collection,
        User $administrator,
        ?string $altText = null
    ): PlatformMedia {
        $mime = $file->getMimeType();

        $extensions = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];

        if (! isset($extensions[$mime])) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported image format.',
            ]);
        }

        if (! in_array(
            $collection,
            self::COLLECTIONS,
            true
        )) {
            throw ValidationException::withMessages([
                'collection' => 'Invalid media collection.',
            ]);
        }

        $dimensions = @getimagesize(
            $file->getRealPath()
        );

        if (
            ! $dimensions
            || $dimensions[0] > 6000
            || $dimensions[1] > 6000
        ) {
            throw ValidationException::withMessages([
                'file' => 'Invalid image dimensions.',
            ]);
        }

        $filename = Str::uuid()
            . '.'
            . $extensions[$mime];

        $path = $file->storeAs(
            'platform/media/' . $collection,
            $filename,
            'public'
        );

        if (! $path) {
            throw ValidationException::withMessages([
                'file' => 'The image could not be stored.',
            ]);
        }

        try {
            return PlatformMedia::create([
                'name' => pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                ),

                'original_name' =>
                    $file->getClientOriginalName(),

                'disk' => 'public',
                'path' => $path,
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'alt_text' => $altText,
                'collection' => $collection,
                'uploaded_by' => $administrator->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }
    }
}