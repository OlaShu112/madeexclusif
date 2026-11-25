<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_themes', function (Blueprint $table) {

            // Website template and typography.

            $table->string('template_key', 100)
                ->default('general');

            $table->string('font_family', 100)
                ->default('system');

            // Announcement bar.

            $table->boolean('announcement_enabled')
                ->default(false);

            $table->string('announcement_text', 500)
                ->nullable();

            $table->string('announcement_colour', 7)
                ->default('#222222');

            // Header and navigation.

            $table->json('navigation_items')
                ->nullable();

            $table->json('header_settings')
                ->nullable();

            // Homepage hero banner.

            $table->string('hero_title', 255)
                ->nullable();

            $table->text('hero_subtitle')
                ->nullable();

            $table->string('hero_image', 500)
                ->nullable();

            $table->string('hero_button_text', 100)
                ->default('Shop Collection');

            // Homepage sections.

            $table->json('homepage_sections')
                ->nullable();

            // Footer configuration.

            $table->json('footer_columns')
                ->nullable();

            // Social media.

            $table->json('social_links')
                ->nullable();

            // Search engine optimisation.

            $table->string('seo_title', 255)
                ->nullable();

            $table->text('seo_description')
                ->nullable();

            // Draft and publication management.

            $table->json('draft_settings')
                ->nullable();

            $table->timestamp('published_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_themes', function (Blueprint $table) {

            $table->dropColumn([
                'template_key',
                'font_family',
                'announcement_enabled',
                'announcement_text',
                'announcement_colour',
                'navigation_items',
                'header_settings',
                'hero_title',
                'hero_subtitle',
                'hero_image',
                'hero_button_text',
                'homepage_sections',
                'footer_columns',
                'social_links',
                'seo_title',
                'seo_description',
                'draft_settings',
                'published_at',
            ]);
        });
    }
};