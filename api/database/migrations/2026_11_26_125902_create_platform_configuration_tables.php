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
        | Platform Settings
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();

            $table->string('group', 100);
            $table->string('key', 150)->unique();
            $table->json('value')->nullable();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('group');
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Media
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_media', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('original_name');
            $table->string('disk', 50)->default('public');
            $table->string('path', 500)->unique();
            $table->string('mime_type', 100);

            $table->unsignedBigInteger('size');

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('alt_text')->nullable();
            $table->string('collection', 50)->default('general');

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('collection');
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Branding
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_branding', function (Blueprint $table) {
            $table->id();

            $table->string('identifier', 50)
                ->default('default')
                ->unique();

            $table->string('platform_name')->default('IVISION');

            $table->string('tagline')
                ->default('Intelligent Digital Technology');

            $table->string('administration_title')
                ->default('Platform Administration');

            $table->string('browser_title')
                ->default('IVISION — Administration Portal');

            $table->foreignId('primary_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('light_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('dark_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('sidebar_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('favicon_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('login_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('login_background_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            $table->foreignId('email_logo_id')
                ->nullable()
                ->constrained('platform_media')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Platform Colours
            |--------------------------------------------------------------------------
            */

            $table->string('primary_colour', 7)
                ->default('#111827');

            $table->string('secondary_colour', 7)
                ->default('#334155');

            $table->string('accent_colour', 7)
                ->default('#C9A86A');

            $table->string('background_colour', 7)
                ->default('#F5F6F9');

            $table->string('text_colour', 7)
                ->default('#111827');

            $table->string('sidebar_colour', 7)
                ->default('#111827');

            $table->string('sidebar_text_colour', 7)
                ->default('#E5E7EB');

            $table->string('active_menu_colour', 7)
                ->default('#374151');

            $table->string('header_colour', 7)
                ->default('#FFFFFF');

            $table->string('footer_colour', 7)
                ->default('#FFFFFF');

            $table->string('button_colour', 7)
                ->default('#111827');

            $table->string('button_text_colour', 7)
                ->default('#FFFFFF');

            $table->string('login_background_colour', 7)
                ->default('#F5F6F9');

            /*
            |--------------------------------------------------------------------------
            | Typography and Copyright
            |--------------------------------------------------------------------------
            */

            $table->string('font_family', 100)
                ->default('system');

            $table->string('appearance', 20)
                ->default('light');

            $table->string('copyright_text')
                ->default('All rights reserved.');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Configuration History
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'platform_setting_histories',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('section', 100);
                $table->string('setting_key', 150)->nullable();

                $table->json('previous_value')->nullable();
                $table->json('new_value')->nullable();

                $table->string('action', 100);

                $table->timestamps();

                $table->index([
                    'section',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_setting_histories');
        Schema::dropIfExists('platform_branding');
        Schema::dropIfExists('platform_media');
        Schema::dropIfExists('platform_settings');
    }
};