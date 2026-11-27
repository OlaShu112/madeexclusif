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
        | Platform Email Templates
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_email_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 100);
            $table->string('subject');

            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->json('variables')->nullable();

            $table->string('language', 10)->default('en');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('created_by', 'fk_email_tpl_creator')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'fk_email_tpl_updater')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['category', 'is_active'],
                'idx_email_tpl_category_active'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Communications
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_communications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_email_template_id')->nullable();
            $table->foreignId('created_by')->nullable();

            $table->string('reference')->unique();
            $table->string('name');

            $table->string('channel', 30)->default('email');
            $table->string('type', 30)->default('operational');

            $table->string('subject')->nullable();
            $table->longText('content');

            $table->string('audience_type', 50)->default('selected');
            $table->json('audience_filters')->nullable();

            $table->string('status', 30)->default('draft');

            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->foreign(
                'platform_email_template_id',
                'fk_comm_email_tpl'
            )
                ->references('id')
                ->on('platform_email_templates')
                ->nullOnDelete();

            $table->foreign('created_by', 'fk_comm_creator')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['status', 'scheduled_at'],
                'idx_comm_status_schedule'
            );

            $table->index(
                ['channel', 'type'],
                'idx_comm_channel_type'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Communication Recipients
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_communication_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_communication_id');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('store_id')->nullable();
            $table->foreignId('email_log_id')->nullable();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone', 30)->nullable();

            $table->string('recipient_key', 191);

            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('attempts')->default(0);

            $table->string('provider_message_id')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();

            $table->timestamps();

            $table->foreign(
                'platform_communication_id',
                'fk_comm_recipient_comm'
            )
                ->references('id')
                ->on('platform_communications')
                ->restrictOnDelete();

            $table->foreign('user_id', 'fk_comm_recipient_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('store_id', 'fk_comm_recipient_store')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();

            $table->foreign('email_log_id', 'fk_comm_recipient_log')
                ->references('id')
                ->on('email_logs')
                ->nullOnDelete();

            $table->unique(
                ['platform_communication_id', 'recipient_key'],
                'platform_comm_recipient_unique'
            );

            $table->index(
                ['platform_communication_id', 'status'],
                'platform_comm_recipient_status'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Announcements
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')->nullable();

            $table->string('title');
            $table->longText('content');

            $table->string('type', 30)->default('information');

            $table->string('audience_type', 50)->default('all');
            $table->json('audience_filters')->nullable();

            $table->string('status', 30)->default('draft');

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_dismissible')->default(true);

            $table->string('action_label')->nullable();
            $table->string('action_url', 500)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->foreign('created_by', 'fk_announcement_creator')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['status', 'starts_at'],
                'idx_announcement_status_start'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Notifications
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');
            $table->foreignId('store_id')->nullable();
            $table->foreignId('platform_communication_id')->nullable();

            $table->string('notification_key')->unique();

            $table->string('type', 50)->default('information');

            $table->string('title');
            $table->text('message');

            $table->string('action_url', 500)->nullable();
            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id', 'fk_platform_notif_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('store_id', 'fk_platform_notif_store')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();

            $table->foreign(
                'platform_communication_id',
                'fk_platform_notif_comm'
            )
                ->references('id')
                ->on('platform_communications')
                ->nullOnDelete();

            $table->index(
                ['user_id', 'read_at'],
                'idx_platform_notif_user_read'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Communication Preferences
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_communication_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique();

            $table->boolean('marketing_email')->default(false);
            $table->boolean('marketing_sms')->default(false);
            $table->boolean('product_updates')->default(false);
            $table->boolean('in_app_notifications')->default(true);

            $table->string('consent_version')->nullable();
            $table->string('consent_source')->nullable();

            $table->timestamp('email_consented_at')->nullable();
            $table->timestamp('sms_consented_at')->nullable();

            $table->timestamp('email_unsubscribed_at')->nullable();
            $table->timestamp('sms_unsubscribed_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id', 'fk_comm_pref_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Communication Attachments
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_communication_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_communication_id');

            $table->string('name');
            $table->string('original_name');

            $table->string('disk', 50)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 100);

            $table->unsignedBigInteger('size');

            $table->timestamps();

            $table->foreign(
                'platform_communication_id',
                'fk_comm_attachment_comm'
            )
                ->references('id')
                ->on('platform_communications')
                ->restrictOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Automation Rules
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_automation_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_email_template_id')->nullable();
            $table->foreignId('created_by')->nullable();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('event', 100);
            $table->string('channel', 30)->default('email');

            $table->unsignedInteger('delay_minutes')->default(0);

            $table->json('conditions')->nullable();
            $table->json('configuration')->nullable();

            $table->boolean('is_active')->default(false);

            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();

            $table->foreign(
                'platform_email_template_id',
                'fk_automation_email_tpl'
            )
                ->references('id')
                ->on('platform_email_templates')
                ->nullOnDelete();

            $table->foreign('created_by', 'fk_automation_creator')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['event', 'is_active'],
                'idx_automation_event_active'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse Migration
    |--------------------------------------------------------------------------
    */

    public function down(): void
    {
        Schema::dropIfExists('platform_automation_rules');
        Schema::dropIfExists('platform_communication_attachments');
        Schema::dropIfExists('platform_communication_preferences');
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('platform_communication_recipients');
        Schema::dropIfExists('platform_communications');
        Schema::dropIfExists('platform_email_templates');
    }
};