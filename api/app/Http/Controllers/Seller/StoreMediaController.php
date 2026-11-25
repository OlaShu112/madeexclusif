<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreMedia;
use App\Services\WebsiteMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreMediaController extends Controller
{
    private function currentStore(Request $request): Store
    {
        $store = $request->attributes->get('store');

        abort_unless(
            $store instanceof Store,
            403,
            'Store access denied.'
        );

        abort_unless(
            (int) $store->user_id ===
                (int) $request->user()->id &&
            $store->status === 'active',
            403,
            'Store access denied.'
        );

        return $store;
    }

    /**
     * Retrieve the business media library.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);

        $data = $request->validate([
            'collection' => [
                'nullable',
                Rule::in(StoreMedia::COLLECTIONS),
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

        $query = $store->media()->latest('id');

        if (!empty($data['collection'])) {
            $query->where(
                'collection',
                $data['collection']
            );
        }

        if (!empty($data['q'])) {
            $search = trim($data['q']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere(
                        'original_name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        return response()->json(
            $query->paginate(
                (int) ($data['per_page'] ?? 24)
            )->withQueryString()
        );
    }

    /**
     * Upload an image.
     */
    public function store(
        Request $request,
        WebsiteMediaService $mediaService
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $data = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=1,min_height=1,max_width=6000,max_height=6000',
            ],

            'collection' => [
                'required',
                Rule::in(StoreMedia::COLLECTIONS),
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $media = $mediaService->upload(
            $store,
            $request->file('image'),
            $data['collection'],
            $data['alt_text'] ?? null
        );

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'media' => $media,
        ], 201);
    }

    /**
     * Retrieve an individual media item.
     */
    public function show(
        Request $request,
        int $media
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $item = $store->media()
            ->findOrFail($media);

        return response()->json([
            'media' => $item,
        ]);
    }

    /**
     * Update image metadata.
     */
    public function update(
        Request $request,
        int $media
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'alt_text' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $item = DB::transaction(function () use (
            $store,
            $media,
            $data,
            $request
        ) {
            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            abort_unless(
                $lockedStore->status === 'active' &&
                (int) $lockedStore->user_id ===
                    (int) $request->user()->id,
                403,
                'Store access denied.'
            );

            $item = $lockedStore->media()
                ->lockForUpdate()
                ->findOrFail($media);

            $item->update($data);

            return $item->refresh();

        }, 3);

        return response()->json([
            'message' => 'Image information updated.',
            'media' => $item,
        ]);
    }
}