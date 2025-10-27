<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contract Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for contract notification email management including
    | approved domains for distribution lists and notification preferences.
    |
    */

    'notifications' => [

        /*
        | Approved Email Domains
        |
        | Only emails from these domains can be added to the notification
        | recipient list. This prevents typos and unauthorized email addresses.
        */
        'approved_domains' => [
            'caclv.org',
        ],

        /*
        | Default Reminder Intervals (days before due date)
        */
        'default_reminder_intervals' => [30, 15, 7, 3, 1, 0],

    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy System Reference
    |--------------------------------------------------------------------------
    |
    | Information about the legacy Contract Database system for reference.
    |
    */

    'legacy_system' => [
        'host' => '10.55.0.10',
        'database' => 'Contracts',
        'migrated_at' => null, // Will be set after migration
        'audit_logs_archived' => false,
    ],

];
