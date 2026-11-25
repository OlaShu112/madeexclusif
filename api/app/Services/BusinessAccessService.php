<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\Request;

class BusinessAccessService
{
    public function currentStore(Request $request): Store
    {
        $store = $request->attributes->get('store');

        abort_unless(
            $store instanceof Store &&
            $request->user() &&
            (int) $store->user_id ===
                (int) $request->user()->id &&
            $store->status === 'active',
            403,
            'You are not authorised to manage this business.'
        );

        return $store;
    }

    public function publicStore(string $slug): Store
    {
        return Store::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
