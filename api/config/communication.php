<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IVISION Platform Communication
    |--------------------------------------------------------------------------
    */

    'enabled' => env(
        'COMMUNICATION_ENABLED',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | Communication Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        'email' => [
            'enabled' => env(
                'COMMUNICATION_EMAIL_ENABLED',
                false
            ),
            'label' => 'Email',
        ],

        'sms' => [
            'enabled' => env(
                'COMMUNICATION_SMS_ENABLED',
                false
            ),
            'label' => 'SMS',
        ],

        'in_app' => [
            'enabled' => env(
                'COMMUNICATION_IN_APP_ENABLED',
                false
            ),
            'label' => 'In-app notification',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Communication Types
    |--------------------------------------------------------------------------
    */

    'types' => [

        'transactional',
        'operational',
        'marketing',

    ],

    /*
    |--------------------------------------------------------------------------
    | Communication Audiences
    |--------------------------------------------------------------------------
    */

    'audiences' => [

        'all',
        'businesses',
        'customers',
        'administrators',
        'selected',
        'subscription_plan',
        'active_subscribers',
        'trial_subscribers',

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [

        'name' => env(
            'COMMUNICATION_QUEUE',
            'communications'
        ),

        'batch_size' => 100,

        'max_attempts' => 3,

        'retry_delay' => 60,

    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [

        'default' => 20,

        'maximum' => 100,

    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */

    'attachments' => [

        'disk' => 'local',

        'directory' => 'communications/attachments',

        'max_size' => 10240,

        'max_files' => 5,

        'allowed_mimes' => [

            'pdf',
            'jpg',
            'jpeg',
            'png',
            'docx',
            'xlsx',
            'txt',

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing Consent
    |--------------------------------------------------------------------------
    */

    'consent' => [

        'require_marketing_email_consent' => true,

        'require_marketing_sms_consent' => true,

        'version' => '1.0',

    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [

        'read' => 120,

        'write' => 30,

        'send' => 5,

    ],

    /*
    |--------------------------------------------------------------------------
    | Communication Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [

        // General access

        'communication.view',

        // Communications

        'communication.messages.view',
        'communication.messages.create',
        'communication.messages.update',
        'communication.messages.delete',
        'communication.messages.send',
        'communication.messages.schedule',
        'communication.messages.cancel',

        // Email templates

        'communication.templates.view',
        'communication.templates.create',
        'communication.templates.update',
        'communication.templates.delete',

        // Announcements

        'communication.announcements.view',
        'communication.announcements.create',
        'communication.announcements.update',
        'communication.announcements.publish',
        'communication.announcements.archive',

        // Notifications

        'communication.notifications.view',
        'communication.notifications.create',

        // Recipients

        'communication.recipients.view',

        // Delivery

        'communication.delivery.view',
        'communication.delivery.retry',

        // Automation

        'communication.automation.view',
        'communication.automation.create',
        'communication.automation.update',
        'communication.automation.manage',

        // Preferences

        'communication.preferences.view',

        // Settings

        'communication.settings.view',
        'communication.settings.manage',

        // Permission management

        'communication.permissions.manage',

    ],

    /*
    |--------------------------------------------------------------------------
    | Route Permission Mapping
    |--------------------------------------------------------------------------
    */

    'route_permissions' => [

        'admin.communication.overview'
            => 'communication.view',

        'admin.communication.messages.index'
            => 'communication.messages.view',

        'admin.communication.templates.index'
            => 'communication.templates.view',

        'admin.communication.announcements.index'
            => 'communication.announcements.view',

        'admin.communication.notifications.index'
            => 'communication.notifications.view',

        'admin.communication.recipients.index'
            => 'communication.recipients.view',

        'admin.communication.delivery.index'
            => 'communication.delivery.view',

        'admin.communication.automation.index'
            => 'communication.automation.view',

        'admin.communication.preferences.index'
            => 'communication.preferences.view',

        'admin.communication.settings.index'
            => 'communication.settings.view',

        'admin.communication.permissions.index'
            => 'communication.view',

    ],

];