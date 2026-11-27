<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformMedia;
use App\Services\PlatformMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformMediaController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'collection' => [
                'nullable',
                Rule::in(
                    PlatformMediaService::COLLECTIONS
                ),
            ],

            'q' => [
                'nullable',
                'string',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $media = PlatformMedia::query()
            ->when(
                $data['collection'] ?? null,
                fn ($query, $collection) =>
                    $query->where(
                        'collection',
                        $collection
                    )
            )
            ->when(
                $data['q'] ?? null,
                fn ($query, $search) =>
                    $query->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
            )
            ->latest('id')
            ->paginate(
                $data['per_page'] ?? 24
            );

        return response()->json($media);
    }

    public function store(
        Request $request,
        PlatformMediaService $media
    ): JsonResponse {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:5120',
                'dimensions:min_width=1,min_height=1,max_width=6000,max_height=6000',
            ],

            'collection' => [
                'required',
                Rule::in(
                    PlatformMediaService::COLLECTIONS
                ),
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $record = $media->upload(
            $request->file('file'),
            $data['collection'],
            $request->user(),
            $data['alt_text'] ?? null
        );

        return response()->json([
            'message' =>
                'Platform image uploaded successfully.',

            'media' => $record,
        ], 201);
    }
}