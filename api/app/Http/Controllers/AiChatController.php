<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $store = Store::query()
            ->where('is_main', true)
            ->where('status', 'active')
            ->first();

        if (! $store) {
            return response()->json([
                'reply' => 'Our store information is temporarily unavailable.',
            ], 503);
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['brand', 'category', 'variants'])
            ->limit(40)
            ->get();

        $catalogue = $products->map(function ($product) {
            return [
                'name' => $product->name,
                'description' => $product->description,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'gender' => $product->gender,
                'concentration' => $product->concentration,
                'top_notes' => $product->notes_top,
                'heart_notes' => $product->notes_heart,
                'base_notes' => $product->notes_base,
                'variants' => $product->variants->map(fn ($variant) => [
                    'size_ml' => $variant->size_ml,
                ])->all(),
            ];
        })->all();

        $context = json_encode([
            'store' => [
                'name' => $store->name,
                'tagline' => $store->tagline,
                'opening_hours' => 'Not configured',
                'delivery_policy' => 'Not configured',
                'returns_policy' => 'Not configured',
            ],
            'products' => $catalogue,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        try {
            $response = Http::timeout(95)
                ->post(
                    config('services.made_exclusif_ai.url', 'http://127.0.0.1:8001') . '/chat',
                    [
                        'message' => $validated['message'],
                        'history' => $validated['history'] ?? [],
                        'context' => $context,
                    ]
                );

            if ($response->failed()) {
                Log::warning('Made Exclusif AI request failed', [
                    'status' => $response->status(),
                ]);

                return response()->json([
                    'reply' => 'Our AI assistant is temporarily unavailable. Please try again.',
                ], 503);
            }

            return response()->json([
                'reply' => $response->json('reply'),
            ]);

        } catch (\Throwable $exception) {
            Log::error('Made Exclusif AI connection failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'reply' => 'Our AI assistant is temporarily unavailable. Please try again.',
            ], 503);
        }
    }
}

