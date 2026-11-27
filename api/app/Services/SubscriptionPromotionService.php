<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionOffer;
use App\Models\SubscriptionCoupon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SubscriptionPromotionService
{
    public function __construct(
        private SubscriptionPricingService $pricing
    ) {
    }

    /**
     * Validate a promotional offer for a business.
     */
    public function validate(
        SubscriptionOffer $offer,
        SubscriptionPlan $plan,
        Store $store,
        string $interval,
        ?SubscriptionCoupon $coupon = null
    ): void {
        if (!$plan->is_active) {
            $this->fail(
                'The subscription plan is not active.'
            );
        }

        if ($store->status !== 'active') {
            $this->fail(
                'The business must be active.'
            );
        }

        if (
            (int) $offer->subscription_plan_id !==
            (int) $plan->id
        ) {
            $this->fail(
                'This offer is not available for the selected plan.'
            );
        }

        if (!$offer->isAvailable()) {
            $this->fail(
                'This promotional offer is unavailable or expired.'
            );
        }

        if (!$offer->supportsInterval($interval)) {
            $this->fail(
                'This offer does not support the selected billing interval.'
            );
        }

        if ($offer->requires_coupon && !$coupon) {
            $this->fail(
                'A valid coupon code is required.'
            );
        }

        if ($coupon) {
            if (
                (int) $coupon->subscription_offer_id !==
                (int) $offer->id
            ) {
                $this->fail(
                    'The coupon does not belong to this offer.'
                );
            }

            if (!$coupon->isAvailable()) {
                $this->fail(
                    'The supplied coupon is unavailable.'
                );
            }
        }

        $this->validateEligibility(
            $offer,
            $store
        );

        // Confirm that the configured discount is valid.

        $this->pricing->calculate(
            $plan,
            $interval,
            $offer
        );
    }

    /**
     * Validate business eligibility.
     */
    public function validateEligibility(
        SubscriptionOffer $offer,
        Store $store
    ): void {
        $hasSubscriptionHistory =
            StoreSubscription::query()
                ->where('store_id', $store->id)
                ->exists();

        $hasCurrentSubscription =
            StoreSubscription::query()
                ->where('store_id', $store->id)

                ->where(function ($query) {
                    $query->where('status', 'active')

                        ->orWhere(function ($query) {
                            $query->where('status', 'trialing')
                                ->where('trial_ends_at', '>', now());
                        });
                })

                ->where(function ($query) {
                    $query->whereNull('current_period_end')
                        ->orWhere('current_period_end', '>', now());
                })

                ->exists();

        switch ($offer->eligibility) {

            case 'new':

                if ($hasSubscriptionHistory) {
                    $this->fail(
                        'This offer is available to first-time subscribers only.'
                    );
                }

                break;

            case 'existing':

                if (!$hasCurrentSubscription) {
                    $this->fail(
                        'This offer is available to existing subscribers only.'
                    );
                }

                break;

            case 'selected':

                $eligibleStores = array_map(
                    'intval',
                    $offer->eligible_store_ids ?? []
                );

                if (!in_array(
                    (int) $store->id,
                    $eligibleStores,
                    true
                )) {
                    $this->fail(
                        'This business is not eligible for the selected offer.'
                    );
                }

                break;

            case 'all':

                break;

            default:

                $this->fail(
                    'Invalid promotional eligibility configuration.'
                );
        }
    }

    /**
     * Retrieve public offers suitable for advertising.
     *
     * Final eligibility is checked during subscription.
     */
    public function publicOffers(
        SubscriptionPlan $plan,
        string $interval
    ): Collection {
        if (!$plan->is_active) {
            return collect();
        }

        return SubscriptionOffer::query()
            ->available()

            ->where(
                'subscription_plan_id',
                $plan->id
            )

            ->where('show_on_website', true)
            ->where('requires_coupon', false)

            ->whereIn(
                'eligibility',
                ['new', 'all']
            )

            ->whereIn(
                'billing_interval',
                [$interval, 'both']
            )

            ->orderByDesc('id')
            ->get();
    }

    /**
     * Retrieve eligible offers for a particular business.
     */
    public function eligibleOffers(
        SubscriptionPlan $plan,
        Store $store,
        string $interval
    ): Collection {
        return SubscriptionOffer::query()
            ->available()

            ->where(
                'subscription_plan_id',
                $plan->id
            )

            ->where('requires_coupon', false)

            ->whereIn(
                'billing_interval',
                [$interval, 'both']
            )

            ->get()

            ->filter(function ($offer) use (
                $plan,
                $store,
                $interval
            ) {
                try {
                    $this->validate(
                        $offer,
                        $plan,
                        $store,
                        $interval
                    );

                    return true;

                } catch (ValidationException $exception) {
                    return false;
                }
            })

            ->values();
    }

    /**
     * Select the lowest-priced eligible promotion.
     *
     * Discounts are not stacked.
     */
    public function bestOffer(
        SubscriptionPlan $plan,
        Store $store,
        string $interval
    ): ?SubscriptionOffer {
        return $this->eligibleOffers(
            $plan,
            $store,
            $interval
        )

        ->sortBy(function ($offer) use (
            $plan,
            $interval
        ) {
            return $this->pricing->calculate(
                $plan,
                $interval,
                $offer
            )['discounted_price_minor'];
        })

        ->first();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'offer' => $message,
        ]);
    }
}