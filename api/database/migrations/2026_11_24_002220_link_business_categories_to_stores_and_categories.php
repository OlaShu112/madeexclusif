<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Connect stores to business categories.
        Schema::table('stores', function (Blueprint $table) {

            $table->foreignId('business_category_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('business_categories')
                ->restrictOnDelete();

        });

        // Connect product categories to business categories.
        Schema::table('categories', function (Blueprint $table) {

            $table->foreignId('business_category_id')
                ->nullable()
                ->after('id')
                ->constrained('business_categories')
                ->restrictOnDelete();

            $table->string('type', 100)
                ->change();

        });

        // Link existing stores and product categories
        // without deleting existing records.
        $businessCategories = DB::table('business_categories')
            ->get(['id', 'slug']);

        foreach ($businessCategories as $category) {

            DB::table('stores')
                ->where('type', $category->slug)
                ->whereNull('business_category_id')
                ->update([
                    'business_category_id' => $category->id,
                ]);

            DB::table('categories')
                ->where('type', $category->slug)
                ->whereNull('business_category_id')
                ->update([
                    'business_category_id' => $category->id,
                ]);
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Automatic rollback is disabled to protect business category relationships.'
        );
    }
};