<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configure GitHub integration to automatically create issues from feedback.
    | You'll need a Personal Access Token with 'repo' scope.
    |
    */
    'github' => [
        'enabled' => env('FEEDBACK_GITHUB_ENABLED', false),
        'token' => env('FEEDBACK_GITHUB_TOKEN'),
        'repo' => env('FEEDBACK_GITHUB_REPO', 'filmdc/HOMES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Integration
    |--------------------------------------------------------------------------
    |
    | Configure Slack integration to post notifications when feedback is submitted.
    | Create an incoming webhook in your Slack workspace.
    |
    */
    'slack' => [
        'enabled' => env('FEEDBACK_SLACK_ENABLED', false),
        'webhook_url' => env('FEEDBACK_SLACK_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for new feedback submissions.
    | Recipients should be comma-separated email addresses.
    |
    */
    'email' => [
        'enabled' => env('FEEDBACK_EMAIL_ENABLED', true),
        'recipients' => array_filter(explode(',', env('FEEDBACK_EMAIL_RECIPIENTS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Labels & Mappings
    |--------------------------------------------------------------------------
    |
    | Define the available categories and their display labels.
    | These map to GitHub labels when integration is enabled.
    |
    */
    'categories' => [
        'bug_report' => [
            'label' => 'Bug Report',
            'github_label' => 'bug',
            'icon' => 'bug-ant',
            'color' => 'red',
        ],
        'feature_request' => [
            'label' => 'Feature Request',
            'github_label' => 'enhancement',
            'icon' => 'light-bulb',
            'color' => 'green',
        ],
        'usability_issue' => [
            'label' => 'Usability Issue',
            'github_label' => 'ux',
            'icon' => 'cursor-arrow-ripple',
            'color' => 'yellow',
        ],
        'question' => [
            'label' => 'Question',
            'github_label' => 'question',
            'icon' => 'question-mark-circle',
            'color' => 'blue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    |
    | Define the available priority levels and their display properties.
    |
    */
    'priorities' => [
        'low' => [
            'label' => 'Low',
            'color' => 'gray',
        ],
        'medium' => [
            'label' => 'Medium',
            'color' => 'blue',
        ],
        'high' => [
            'label' => 'High',
            'color' => 'amber',
        ],
        'critical' => [
            'label' => 'Critical',
            'color' => 'red',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Workflow
    |--------------------------------------------------------------------------
    |
    | Define the available statuses and their display properties.
    |
    */
    'statuses' => [
        'new' => [
            'label' => 'New',
            'color' => 'blue',
        ],
        'acknowledged' => [
            'label' => 'Acknowledged',
            'color' => 'yellow',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'color' => 'purple',
        ],
        'resolved' => [
            'label' => 'Resolved',
            'color' => 'green',
        ],
        'closed' => [
            'label' => 'Closed',
            'color' => 'gray',
        ],
        'wont_fix' => [
            'label' => "Won't Fix",
            'color' => 'red',
        ],
    ],
];
