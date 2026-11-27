<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionOffer;
use App\Models\SubscriptionCoupon;
use Illuminate\Validation\ValidationException;

class SubscriptionPricingService
{
    /**
     * Calculate standard and promotional pricing.
     *
     * All monetary values are stored in minor units.
     */
    public function calculate(
        SubscriptionPlan $plan,
        string $interval,
        ?SubscriptionOffer $offer = null
    ): array {
        if (!in_array(
            $interval,
            ['monthly', 'annual'],
            true
        )) {
            $this->fail('billing_interval', 'Invalid billing interval.');
        }

        if (strtolower($plan->currency) !== 'gbp') {
            $this->fail('currency', 'Only GBP is currently supported.');
        }

        $standardPrice = $plan->priceFor($interval);

        $discountedPrice = $standardPrice;

        $durationType = null;
        $durationPeriods = null;

        if ($offer) {
            if (
                (int) $offer->subscription_plan_id !==
                (int) $plan->id
            ) {
                $this->fail(
                    'offer',
                    'This offer belongs to another subscription plan.'
                );
            }

            if (!$offer->supportsInterval($interval)) {
                $this->fail(
                    'offer',
                    'This offer does not support the billing interval.'
                );
            }

            $discountedPrice = match ($offer->discount_type) {

                'fixed_price' =>
                    $this->fixedPrice(
                        $offer,
                        $interval
                    ),

                'fixed_amount' =>
                    $this->fixedAmount(
                        $standardPrice,
                        $offer
                    ),

                'percentage' =>
                    $this->percentagePrice(
                        $standardPrice,
                        $offer
                    ),

                default => throw ValidationException::withMessages([
                    'discount_type' =>
                        'Invalid discount type.',
                ]),
            };

            if (
                $discountedPrice < 0 ||
                $discountedPrice > $standardPrice
            ) {
                $this->fail(
                    'offer',
                    'The promotional price must be between zero and the standard price.'
                );
            }

            $durationType = $offer->duration_type;

            $durationPeriods = match ($durationType) {
                'first_period' => 1,

                'limited_periods' =>
                    $offer->duration_periods,

                'lifetime' => null,

                default => throw ValidationException::withMessages([
                    'duration_type' =>
                        'Invalid discount duration.',
                ]),
            };

            if (
                $durationType === 'limited_periods' &&
                (
                    $durationPeriods === null ||
                    $durationPeriods < 1
                )
            ) {
                $this->fail(
                    'duration_periods',
                    'Enter a valid number of billing periods.'
                );
            }
        }

        $saving = $standardPrice - $discountedPrice;

        $savingPercentage = $standardPrice > 0
            ? round(($saving / $standardPrice) * 100, 2)
            : 0;

        return [
            'subscription_plan_id' => $plan->id,
            'plan_name' => $plan->name,

            'subscription_offer_id' => $offer?->id,
            'offer_name' => $offer?->name,

            'billing_interval' => $interval,
            'currency' => 'gbp',

            'standard_price_minor' => $standardPrice,
            'discounted_price_minor' => $discountedPrice,

            'renewal_price_minor' =>
                $durationType === 'lifetime'
                    ? $discountedPrice
                    : $standardPrice,

            'discount_amount_minor' => $saving,
            'discount_percentage' => $savingPercentage,

            'discount_duration_type' => $durationType,
            'discount_duration_periods' => $durationPeriods,

            'has_discount' => $saving > 0,
        ];
    }

    /**
     * Use the administrator's specified offer price.
     */
    private function fixedPrice(
        SubscriptionOffer $offer,
        string $interval
    ): int {
        $price = $interval === 'monthly'
            ? $offer->monthly_discount_price_minor
            : $offer->annual_discount_price_minor;

        if ($price === null) {
            $this->fail(
                'offer',
                'No promotional price has been configured for this billing interval.'
            );
        }

        return (int) $price;
    }

    /**
     * Subtract a fixed monetary discount.
     */
    private function fixedAmount(
        int $standardPrice,
        SubscriptionOffer $offer
    ): int {
        $amount = $offer->discount_amount_minor;

        if (
            $amount === null ||
            $amount < 0 ||
            $amount > $standardPrice
        ) {
            $this->fail(
                'discount_amount_minor',
                'The discount amount is invalid.'
            );
        }

        return $standardPrice - $amount;
    }

    /**
     * Calculate percentage discounts using basis points.
     *
     * 40.00% = 4000 basis points.
     */
    private function percentagePrice(
        int $standardPrice,
        SubscriptionOffer $offer
    ): int {
        $percentage = $offer->discount_percentage;

        if (
            $percentage === null ||
            !is_numeric($percentage) ||
            $percentage < 0 ||
            $percentage > 100
        ) {
            $this->fail(
                'discount_percentage',
                'Discount percentage must be between 0 and 100.'
            );
        }

        $basisPoints = (int) round(
            (float) $percentage * 100
        );

        // Round the final price to the nearest penny.

        $remainingBasisPoints = 10000 - $basisPoints;

        return intdiv(
            ($standardPrice * $remainingBasisPoints) + 5000,
            10000
        );
    }

    /**
     * Prepare agreed pricing attributes for a subscription.
     *
     * Call only after eligibility and coupon validation.
     */
    public function snapshot(
        array $pricing,
        ?SubscriptionCoupon $coupon = null
    ): array {
        $agreedAt = now();

        return [
            'subscription_offer_id' =>
                $pricing['subscription_offer_id'],

            'subscription_coupon_id' =>
                $coupon?->id,

            'standard_price_minor' =>
                $pricing['standard_price_minor'],

            'discounted_price_minor' =>
                $pricing['has_discount']
                    ? $pricing['discounted_price_minor']
                    : null,

            'renewal_price_minor' =>
                $pricing['renewal_price_minor'],

            'pricing_currency' =>
                $pricing['currency'],

            'discount_duration_type' =>
                $pricing['discount_duration_type'],

            'discount_duration_periods' =>
                $pricing['discount_duration_periods'],

            'discount_periods_used' => 0,

            // Set when discounted billing actually begins.

            'discount_starts_at' => null,
            'discount_ends_at' => null,

            'pricing_agreed_at' => $agreedAt,

            'pricing_snapshot' => [
                ...$pricing,

                'coupon_id' => $coupon?->id,

                'agreed_at' =>
                    $agreedAt->toIso8601String(),
            ],
        ];
    }

    private function fail(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}