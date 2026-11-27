<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Subscription Promotional Offers
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('subscription_offers')) {

            Schema::create('subscription_offers', function (Blueprint $table) {

                $table->id();

                $table->foreignId('subscription_plan_id')
                    ->constrained('subscription_plans')
                    ->restrictOnDelete();

                $table->string('name');

                $table->string('slug')->unique();

                $table->text('description')->nullable();

                // fixed_price, fixed_amount or percentage

                $table->string('discount_type', 30)
                    ->default('fixed_price');

                // Monetary values stored in minor currency units

                $table->unsignedBigInteger('monthly_discount_price_minor')
                    ->nullable();

                $table->unsignedBigInteger('annual_discount_price_minor')
                    ->nullable();

                $table->unsignedBigInteger('discount_amount_minor')
                    ->nullable();

                $table->decimal('discount_percentage', 5, 2)
                    ->nullable();

                // monthly, annual or both

                $table->string('billing_interval', 20)
                    ->default('both');

                // first_period, limited_periods or lifetime

                $table->string('duration_type', 30)
                    ->default('first_period');

                $table->unsignedInteger('duration_periods')
                    ->nullable();

                // new, existing, all or selected

                $table->string('eligibility', 30)
                    ->default('new');

                $table->json('eligible_store_ids')
                    ->nullable();

                $table->timestamp('starts_at')
                    ->nullable();

                $table->timestamp('ends_at')
                    ->nullable();

                $table->unsignedInteger('redemption_limit')
                    ->nullable();

                $table->unsignedInteger('redemptions_count')
                    ->default(0);

                $table->boolean('requires_coupon')
                    ->default(false);

                $table->boolean('is_active')
                    ->default(false);

                $table->boolean('show_on_website')
                    ->default(false);

                $table->string('badge_text', 100)
                    ->nullable();

                $table->string('banner_text', 255)
                    ->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'subscription_plan_id',
                    'is_active',
                ]);

                $table->index([
                    'starts_at',
                    'ends_at',
                ]);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Subscription Coupons
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('subscription_coupons')) {

            Schema::create('subscription_coupons', function (Blueprint $table) {

                $table->id();

                $table->foreignId('subscription_offer_id')
                    ->constrained('subscription_offers')
                    ->restrictOnDelete();

                $table->string('code', 100)->unique();

                $table->string('name')->nullable();

                $table->text('description')->nullable();

                $table->timestamp('starts_at')
                    ->nullable();

                $table->timestamp('ends_at')
                    ->nullable();

                $table->unsignedInteger('redemption_limit')
                    ->nullable();

                $table->unsignedInteger('redemptions_count')
                    ->default(0);

                $table->unsignedInteger('max_uses_per_store')
                    ->default(1);

                $table->boolean('is_active')
                    ->default(false);

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'subscription_offer_id',
                    'is_active',
                ]);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Extend Existing Subscription Records
        |--------------------------------------------------------------------------
        |
        | Each column is checked individually to support recovery from
        | a partially completed migration without deleting existing data.
        |
        */

        Schema::table('store_subscriptions', function (Blueprint $table) {

            if (!Schema::hasColumn(
                'store_subscriptions',
                'subscription_offer_id'
            )) {
                $table->foreignId('subscription_offer_id')
                    ->nullable()
                    ->constrained('subscription_offers')
                    ->restrictOnDelete();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'subscription_coupon_id'
            )) {
                $table->foreignId('subscription_coupon_id')
                    ->nullable()
                    ->constrained('subscription_coupons')
                    ->restrictOnDelete();
            }

            // Original agreed subscription price

            if (!Schema::hasColumn(
                'store_subscriptions',
                'standard_price_minor'
            )) {
                $table->unsignedBigInteger('standard_price_minor')
                    ->nullable();
            }

            // Agreed promotional price

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discounted_price_minor'
            )) {
                $table->unsignedBigInteger('discounted_price_minor')
                    ->nullable();
            }

            // Price after promotional period ends

            if (!Schema::hasColumn(
                'store_subscriptions',
                'renewal_price_minor'
            )) {
                $table->unsignedBigInteger('renewal_price_minor')
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'pricing_currency'
            )) {
                $table->string('pricing_currency', 3)
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discount_duration_type'
            )) {
                $table->string('discount_duration_type', 30)
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discount_duration_periods'
            )) {
                $table->unsignedInteger('discount_duration_periods')
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discount_periods_used'
            )) {
                $table->unsignedInteger('discount_periods_used')
                    ->default(0);
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discount_starts_at'
            )) {
                $table->timestamp('discount_starts_at')
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'discount_ends_at'
            )) {
                $table->timestamp('discount_ends_at')
                    ->nullable();
            }

            // Snapshot of agreed pricing terms

            if (!Schema::hasColumn(
                'store_subscriptions',
                'pricing_snapshot'
            )) {
                $table->json('pricing_snapshot')
                    ->nullable();
            }

            if (!Schema::hasColumn(
                'store_subscriptions',
                'pricing_agreed_at'
            )) {
                $table->timestamp('pricing_agreed_at')
                    ->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Subscription Offer Redemptions
        |--------------------------------------------------------------------------
        |
        | Create the table only if it does not already exist.
        | Indexes are handled separately to recover the failed migration.
        |
        */

        if (!Schema::hasTable('subscription_offer_redemptions')) {

            Schema::create(
                'subscription_offer_redemptions',
                function (Blueprint $table) {

                    $table->id();

                    $table->foreignId('subscription_offer_id')
                        ->constrained('subscription_offers')
                        ->restrictOnDelete();

                    $table->foreignId('subscription_coupon_id')
                        ->nullable()
                        ->constrained('subscription_coupons')
                        ->restrictOnDelete();

                    $table->foreignId('store_id')
                        ->constrained('stores')
                        ->restrictOnDelete();

                    $table->foreignId('store_subscription_id')
                        ->constrained('store_subscriptions')
                        ->restrictOnDelete();

                    $table->string('billing_interval', 20);

                    $table->unsignedBigInteger('standard_price_minor');

                    $table->unsignedBigInteger('discounted_price_minor');

                    $table->unsignedBigInteger('discount_amount_minor');

                    $table->string('currency', 3)
                        ->default('gbp');

                    $table->string('status', 30)
                        ->default('pending');

                    $table->timestamp('redeemed_at')
                        ->nullable();

                    $table->timestamp('expires_at')
                        ->nullable();

                    $table->timestamps();
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Subscription Offer Redemption Indexes
        |--------------------------------------------------------------------------
        |
        | MariaDB and MySQL restrict index identifiers to 64 characters.
        | The unique index uses a shorter explicit name.
        |
        | Existing indexes are checked before creation.
        |
        */

        if (!Schema::hasIndex(
            'subscription_offer_redemptions',
            ['subscription_offer_id', 'store_subscription_id'],
            'unique'
        )) {
            Schema::table(
                'subscription_offer_redemptions',
                function (Blueprint $table) {

                    $table->unique(
                        [
                            'subscription_offer_id',
                            'store_subscription_id',
                        ],
                        'offer_redemption_subscription_unique'
                    );
                }
            );
        }

        if (!Schema::hasIndex(
            'subscription_offer_redemptions',
            ['store_id', 'status']
        )) {
            Schema::table(
                'subscription_offer_redemptions',
                function (Blueprint $table) {

                    $table->index(
                        [
                            'store_id',
                            'status',
                        ],
                        'offer_redemptions_store_status_index'
                    );
                }
            );
        }

        if (!Schema::hasIndex(
            'subscription_offer_redemptions',
            ['subscription_coupon_id', 'status']
        )) {
            Schema::table(
                'subscription_offer_redemptions',
                function (Blueprint $table) {

                    $table->index(
                        [
                            'subscription_coupon_id',
                            'status',
                        ],
                        'offer_redemptions_coupon_status_index'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Subscription Price History
        |--------------------------------------------------------------------------
        |
        | Maintains an audit trail of subscription pricing changes,
        | promotional offers and administrative pricing decisions.
        |
        */

        if (!Schema::hasTable('subscription_price_histories')) {

            Schema::create(
                'subscription_price_histories',
                function (Blueprint $table) {

                    $table->id();

                    $table->foreignId('subscription_plan_id')
                        ->constrained('subscription_plans')
                        ->restrictOnDelete();

                    $table->foreignId('subscription_offer_id')
                        ->nullable()
                        ->constrained('subscription_offers')
                        ->restrictOnDelete();

                    $table->string('action', 50);

                    $table->string('billing_interval', 20)
                        ->nullable();

                    $table->unsignedBigInteger('previous_price_minor')
                        ->nullable();

                    $table->unsignedBigInteger('new_price_minor')
                        ->nullable();

                    $table->string('currency', 3)
                        ->default('gbp');

                    $table->json('previous_values')
                        ->nullable();

                    $table->json('new_values')
                        ->nullable();

                    $table->text('reason')
                        ->nullable();

                    $table->foreignId('changed_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    $table->timestamps();
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Subscription Price History Index
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasIndex(
            'subscription_price_histories',
            ['subscription_plan_id', 'created_at']
        )) {
            Schema::table(
                'subscription_price_histories',
                function (Blueprint $table) {

                    $table->index(
                        [
                            'subscription_plan_id',
                            'created_at',
                        ],
                        'subscription_price_history_plan_date_index'
                    );
                }
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse Migration
    |--------------------------------------------------------------------------
    |
    | Removes subscription pricing functionality in dependency order.
    | Only execute a rollback when intentionally removing this feature.
    |
    */

    public function down(): void
    {
        Schema::dropIfExists('subscription_price_histories');

        Schema::dropIfExists('subscription_offer_redemptions');

        if (Schema::hasTable('store_subscriptions')) {

            Schema::table(
                'store_subscriptions',
                function (Blueprint $table) {

                    if (Schema::hasColumn(
                        'store_subscriptions',
                        'subscription_offer_id'
                    )) {
                        $table->dropConstrainedForeignId(
                            'subscription_offer_id'
                        );
                    }

                    if (Schema::hasColumn(
                        'store_subscriptions',
                        'subscription_coupon_id'
                    )) {
                        $table->dropConstrainedForeignId(
                            'subscription_coupon_id'
                        );
                    }

                    $pricingColumns = [
                        'standard_price_minor',
                        'discounted_price_minor',
                        'renewal_price_minor',
                        'pricing_currency',
                        'discount_duration_type',
                        'discount_duration_periods',
                        'discount_periods_used',
                        'discount_starts_at',
                        'discount_ends_at',
                        'pricing_snapshot',
                        'pricing_agreed_at',
                    ];

                    $existingColumns = [];

                    foreach ($pricingColumns as $column) {

                        if (Schema::hasColumn(
                            'store_subscriptions',
                            $column
                        )) {
                            $existingColumns[] = $column;
                        }
                    }

                    if (!empty($existingColumns)) {
                        $table->dropColumn($existingColumns);
                    }
                }
            );
        }

        Schema::dropIfExists('subscription_coupons');

        Schema::dropIfExists('subscription_offers');
    }
};