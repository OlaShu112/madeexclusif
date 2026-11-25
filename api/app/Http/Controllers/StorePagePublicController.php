<?php

namespace App\Http\Controllers;

use App\Models\StorePage;
use App\Services\BusinessAccessService;

class StorePagePublicController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    public function index(string $store)
    {
        $business = $this->access->publicStore($store);

        return response()->json(
            StorePage::where('store_id', $business->id)
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->get([
                    'title',
                    'slug',
                    'type',
                    'show_in_header',
                    'show_in_footer',
                ])
        );
    }

    public function show(string $store, string $slug)
    {
        $business = $this->access->publicStore($store);

        return response()->json(
            StorePage::where('store_id', $business->id)
                ->where('slug', $slug)
                ->where('is_published', true)
                ->firstOrFail([
                    'title',
                    'slug',
                    'type',
                    'content',
                    'seo_title',
                    'seo_description',
                    'published_at',
                ])
        );
    }
}
