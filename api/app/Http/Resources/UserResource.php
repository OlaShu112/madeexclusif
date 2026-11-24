<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $store = $this->store;

        return [
            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'phone' => $this->phone,

            'role' => $this->role,

            'email_verified' => $this->hasVerifiedEmail(),

            'email_verified_at' => $this->email_verified_at
                ?->toIso8601String(),

            'created_at' => $this->created_at
                ?->toIso8601String(),

            'store' => $store ? [
                'name' => $store->name,

                'slug' => $store->slug,

                'status' => $store->status,

                'logo_url' => $store->logo_url,
            ] : null,
        ];
    }
}