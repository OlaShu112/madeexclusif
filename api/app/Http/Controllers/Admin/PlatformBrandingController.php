<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBranding;
use App\Services\PlatformBrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformBrandingController extends Controller
{
    public function __construct(
        private PlatformBrandingService $branding
    ) {}

    public function publicShow(): JsonResponse
    {
        return response()->json(
            $this->branding->publicBranding()
        );
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'branding' =>
                $this->branding->publicBranding(),

            'defaults' =>
                $this->branding->defaults(),
        ]);
    }

    public function update(
        Request $request
    ): JsonResponse {
        $rules = [
            'platform_name' =>
                ['sometimes', 'required', 'string', 'max:100'],

            'tagline' =>
                ['sometimes', 'required', 'string', 'max:255'],

            'administration_title' =>
                ['sometimes', 'required', 'string', 'max:100'],

            'browser_title' =>
                ['sometimes', 'required', 'string', 'max:255'],

            'copyright_text' =>
                ['sometimes', 'required', 'string', 'max:255'],

            'font_family' => [
                'sometimes',
                'string',
                'in:system,Arial,Inter,Roboto,Georgia',
            ],

            'appearance' => [
                'sometimes',
                'string',
                'in:light,dark',
            ],
        ];

        foreach (
            PlatformBranding::COLOUR_FIELDS as $field
        ) {
            $rules[$field] = [
                'sometimes',
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ];
        }

        foreach (
            PlatformBranding::MEDIA_FIELDS as $field
        ) {
            $rules[$field] = [
                'sometimes',
                'nullable',
                'integer',
                'exists:platform_media,id',
            ];
        }

        $data = $request->validate($rules);

        $this->branding->update(
            $data,
            $request->user()
        );

        return response()->json([
            'message' =>
                'Platform branding updated successfully.',

            'branding' =>
                $this->branding->publicBranding(),
        ]);
    }

    public function reset(
        Request $request
    ): JsonResponse {
        $this->branding->reset(
            $request->user()
        );

        return response()->json([
            'message' =>
                'Default platform branding restored.',

            'branding' =>
                $this->branding->publicBranding(),
        ]);
    }
}