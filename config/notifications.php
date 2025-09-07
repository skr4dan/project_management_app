<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification settings for different types of events.
    | You can enable/disable specific notification types and configure
    | their behavior.
    |
    */

    'task_assigned' => [
        'enabled' => env('NOTIFICATIONS_TASK_ASSIGNED_ENABLED', true),
        'queue' => env('NOTIFICATIONS_TASK_ASSIGNED_QUEUE', 'notifications'),
        'delay' => env('NOTIFICATIONS_TASK_ASSIGNED_DELAY', 0), // seconds
        'deduplication_ttl' => env('NOTIFICATIONS_TASK_ASSIGNED_DEDUPLICATION_TTL', 300), // 5 minutes
    ],

    'task_status' => [
        'enabled' => env('NOTIFICATIONS_TASK_STATUS_ENABLED', true),
        'queue' => env('NOTIFICATIONS_TASK_STATUS_QUEUE', 'notifications'),
        'delay' => env('NOTIFICATIONS_TASK_STATUS_DELAY', 0), // seconds
        'deduplication_ttl' => env('NOTIFICATIONS_TASK_STATUS_DEDUPLICATION_TTL', 180), // 3 minutes
    ],

    'project_status' => [
        'enabled' => env('NOTIFICATIONS_PROJECT_STATUS_ENABLED', true),
        'queue' => env('NOTIFICATIONS_PROJECT_STATUS_QUEUE', 'notifications'),
        'delay' => env('NOTIFICATIONS_PROJECT_STATUS_DELAY', 0), // seconds
        'deduplication_ttl' => env('NOTIFICATIONS_PROJECT_STATUS_DEDUPLICATION_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure email-specific settings for notifications.
    |
    */

    'email' => [
        'from_address' => env('NOTIFICATIONS_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('NOTIFICATIONS_FROM_NAME', 'Project Management System'),

        // BCC all notifications to admin for monitoring
        'bcc_admin' => env('NOTIFICATIONS_BCC_ADMIN', false),
        'admin_email' => env('NOTIFICATIONS_ADMIN_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue connection and settings for notifications.
    |
    */

    'queue' => [
        'connection' => env('NOTIFICATIONS_QUEUE_CONNECTION', 'database'),
        'default_queue' => env('NOTIFICATIONS_DEFAULT_QUEUE', 'notifications'),
        'retry_attempts' => env('NOTIFICATIONS_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('NOTIFICATIONS_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for notifications to prevent spam.
    |
    */

    'rate_limiting' => [
        'enabled' => env('NOTIFICATIONS_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('NOTIFICATIONS_MAX_PER_MINUTE', 10),
        'max_per_hour' => env('NOTIFICATIONS_MAX_PER_HOUR', 100),
    ],
];
