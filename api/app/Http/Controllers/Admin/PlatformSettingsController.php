<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function __construct(
        private PlatformSettingsService $settings
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->settings->all()
        );
    }

    public function update(
        Request $request,
        string $group
    ): JsonResponse {
        $rules = match ($group) {
            'general' => [
                'domain' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:253',
                    'regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,63}$/',
                ],

                'timezone' => [
                    'sometimes',
                    'required',
                    'timezone',
                ],

                'language' => [
                    'sometimes',
                    'required',
                    'in:en-GB,en-US',
                ],

                'currency' => [
                    'sometimes',
                    'required',
                    'in:GBP',
                ],
            ],

            'company' => [
                'registered_name' =>
                    ['sometimes', 'required', 'string', 'max:255'],

                'registration_number' =>
                    ['sometimes', 'nullable', 'string', 'max:100'],

                'vat_number' =>
                    ['sometimes', 'nullable', 'string', 'max:100'],

                'address' =>
                    ['sometimes', 'nullable', 'string', 'max:1000'],

                'telephone' =>
                    ['sometimes', 'nullable', 'string', 'max:50'],
            ],

            'contact' => [
                'support_email' =>
                    ['sometimes', 'nullable', 'email', 'max:255'],

                'administration_email' =>
                    ['sometimes', 'nullable', 'email', 'max:255'],

                'billing_email' =>
                    ['sometimes', 'nullable', 'email', 'max:255'],

                'privacy_email' =>
                    ['sometimes', 'nullable', 'email', 'max:255'],
            ],

            'website' => [
                'seo_title' =>
                    ['sometimes', 'nullable', 'string', 'max:255'],

                'seo_description' =>
                    ['sometimes', 'nullable', 'string', 'max:500'],

                'facebook' =>
                    ['sometimes', 'nullable', 'url:https', 'max:500'],

                'instagram' =>
                    ['sometimes', 'nullable', 'url:https', 'max:500'],

                'linkedin' =>
                    ['sometimes', 'nullable', 'url:https', 'max:500'],

                'youtube' =>
                    ['sometimes', 'nullable', 'url:https', 'max:500'],
            ],

            default => abort(
                404,
                'Settings group not found.'
            ),
        };

        $data = $request->validate($rules);

        return response()->json([
            'message' =>
                'Platform settings saved successfully.',

            'settings' => $this->settings->update(
                $group,
                $data,
                $request->user()
            ),
        ]);
    }
}