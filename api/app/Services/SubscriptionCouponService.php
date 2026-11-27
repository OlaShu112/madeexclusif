<?php

namespace App\Services;

use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionCoupon;
use App\Models\SubscriptionOfferRedemption;
use Illuminate\Validation\ValidationException;

class SubscriptionCouponService
{
    public function __construct(
        private SubscriptionPromotionService $promotions,
        private SubscriptionPricingService $pricing
    ) {
    }

    /**
     * Normalise coupon codes.
     */
    public function normalise(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Find and validate a coupon.
     *
     * This is a read-only validation operation.
     */
    public function validate(
        string $code,
        SubscriptionPlan $plan,
        Store $store,
        string $interval
    ): SubscriptionCoupon {
        $code = $this->normalise($code);

        if (
            $code === '' ||
            strlen($code) > 100 ||
            !preg_match('/^[A-Z0-9_-]+$/', $code)
        ) {
            $this->fail(
                'Enter a valid coupon code.'
            );
        }

        $coupon = SubscriptionCoupon::query()
            ->with('offer')
            ->where('code', $code)
            ->first();

        if (!$coupon) {
            $this->fail(
                'The supplied coupon code is invalid.'
            );
        }

        $this->validateCoupon(
            $coupon,
            $plan,
            $store,
            $interval
        );

        return $coupon;
    }

    /**
     * Validate an existing coupon record.
     */
    public function validateCoupon(
        SubscriptionCoupon $coupon,
        SubscriptionPlan $plan,
        Store $store,
        string $interval
    ): void {
        if (!$coupon->isAvailable()) {
            $this->fail(
                'This coupon is inactive, expired or fully redeemed.'
            );
        }

        $offer = $coupon->offer;

        if (!$offer) {
            $this->fail(
                'The promotional offer is unavailable.'
            );
        }

        $this->promotions->validate(
            $offer,
            $plan,
            $store,
            $interval,
            $coupon
        );

        $this->validateStoreUsage(
            $coupon,
            $store
        );
    }

    /**
     * Check the business-specific redemption limit.
     */
    public function validateStoreUsage(
        SubscriptionCoupon $coupon,
        Store $store
    ): void {
        $uses = SubscriptionOfferRedemption::query()

            ->where(
                'subscription_coupon_id',
                $coupon->id
            )

            ->where('store_id', $store->id)

            ->where(function ($query) {
                $query->where('status', 'redeemed')

                    ->orWhere(function ($query) {
                        $query->where('status', 'pending')

                            ->where(function ($query) {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });
                    });
            })

            ->count();

        if ($uses >= $coupon->max_uses_per_store) {
            $this->fail(
                'This business has reached the coupon usage limit.'
            );
        }
    }

    /**
     * Return a validated coupon pricing preview.
     *
     * Does not reserve or consume a redemption.
     */
    public function preview(
        string $code,
        SubscriptionPlan $plan,
        Store $store,
        string $interval
    ): array {
        $coupon = $this->validate(
            $code,
            $plan,
            $store,
            $interval
        );

        $pricing = $this->pricing->calculate(
            $plan,
            $interval,
            $coupon->offer
        );

        return [
            'valid' => true,

            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
            ],

            'pricing' => $pricing,
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'coupon_code' => $message,
        ]);
    }
}