<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Contract Database Import Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines settings for importing data from the
    | legacy contract database system into CAP TRACK.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Import File Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths where the exported data files should be placed.
    | These paths are relative to the storage/import directory.
    |
    */
    'import_files' => [
        'users' => 'users.json',
        'programs' => 'programs.json',
        'funders' => 'funders.json',
        'contracts' => 'contracts.json',
        'contract_programs' => 'contract_programs.json',
        'report_due_dates' => 'report_due_dates.json',
        'due_date_emails' => 'due_date_emails.json',
        'due_date_email_assignments' => 'due_date_email_assignments.json',
        'media' => 'media.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Alternative Files
    |--------------------------------------------------------------------------
    |
    | Alternative CSV file paths if JSON export doesn't work with your MySQL version.
    |
    */
    'csv_files' => [
        'users' => 'users.csv',
        'programs' => 'programs.csv',
        'funders' => 'funders.csv',
        'contracts' => 'contracts.csv',
        'contract_programs' => 'contract_programs.csv',
        'report_due_dates' => 'report_due_dates.csv',
        'due_date_emails' => 'due_date_emails.csv',
        'due_date_email_assignments' => 'due_date_email_assignments.csv',
        'media' => 'media.csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Program Name Mapping
    |--------------------------------------------------------------------------
    |
    | Map old system program names to new system program IDs.
    | This handles cases where program names or IDs differ between systems.
    |
    | Format: 'old_program_name' => new_program_id
    |
    | Example:
    | 'Energy Assistance' => 1,
    | 'CSBG' => 2,
    | 'Multi' => 3,  // Contract management only program
    |
    */
    'program_mapping' => [
        // Add your program mappings here
        // 'Old Program Name' => new_program_id,

        // Common examples (update with your actual mappings):
        'CALV' => 5,
        'CADB' => 11,
        'CADA' => 3,
        'SSS' => 4,
        'HCP' => 1,
        'HRP' => 2,
        'RTCLF' => 6,
        'SBR' => 7,
        'SHFB' => 8,
        'REJ' => 9,
        'WX' => 10,
        'Multi' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Program for Unmapped Contracts
    |--------------------------------------------------------------------------
    |
    | Program ID to use when a contract's program cannot be mapped.
    | This should typically be a "Contract Management Only" program.
    |
    */
    'default_program_id' => 5, // Set this to your "Multi" or "Contract Only" program ID

    /*
    |--------------------------------------------------------------------------
    | User Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for imported users.
    |
    */
    'user_defaults' => [
        'active' => true,
        'email_verified_at' => null, // Will be set to current timestamp if user was approved in old system
        'password' => null, // Users will need to reset their passwords
    ],

    /*
    |--------------------------------------------------------------------------
    | Funder Name Mapping
    |--------------------------------------------------------------------------
    |
    | Map old funder names to standardized names if needed.
    | This helps clean up inconsistent funder naming.
    |
    */
    'funder_mapping' => [
        // Examples:
        // 'US Dept of Energy' => 'U.S. Department of Energy',
        // 'State of Nevada' => 'State of Nevada',
        // 'Clark County' => 'Clark County, Nevada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Batch Settings
    |--------------------------------------------------------------------------
    |
    | Settings for batch processing during import.
    |
    */
    'batch_size' => 100, // Number of records to process in each batch
    'memory_limit' => '256M', // Memory limit for import process

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data validation during import.
    |
    */
    'validation' => [
        'require_program_mapping' => true, // Fail if a program cannot be mapped
        'require_funder_match' => false, // Fail if a funder cannot be created/matched
        'allow_duplicate_emails' => false, // Allow importing users with duplicate emails
        'skip_invalid_dates' => false, // Skip records with invalid dates vs. fail import
        'minimum_contract_amount' => 0, // Minimum valid contract amount
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Interval Defaults
    |--------------------------------------------------------------------------
    |
    | Default reminder intervals for imported report due dates.
    | Since the old system didn't have configurable intervals,
    | we'll apply these defaults to all imported due dates.
    |
    */
    'default_reminder_intervals' => [30, 15, 7, 3, 1], // Days before due date

    /*
    |--------------------------------------------------------------------------
    | Import Mode Settings
    |--------------------------------------------------------------------------
    |
    | Control how the import handles existing data.
    |
    */
    'import_mode' => [
        'update_existing_users' => false, // Update existing users or skip
        'update_existing_funders' => true, // Update existing funders or skip
        'update_existing_contracts' => false, // Update existing contracts or skip
        'preserve_timestamps' => true, // Preserve original created_at/updated_at dates
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Mapping
    |--------------------------------------------------------------------------
    |
    | Map old system status values to new system values.
    |
    */
    'status_mapping' => [
        'report_due_dates' => [
            'incomplete' => 'pending',
            'completed' => 'completed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media/File Migration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for migrating contract documents and files.
    |
    */
    'media_migration' => [
        'source_storage_path' => '/var/www/Contracts_Database_Laravel_Gen/storage/app', // Path to old system's storage (will check both public and private)
        'remote_server' => [
            'enabled' => true, // Set to true to fetch files from remote server instead of local paths
            'base_url' => 'https://contracts.caclv.org', // Base URL of the remote server
            'storage_path' => '/storage', // Path on remote server where files are stored
            'timeout' => 30, // HTTP timeout in seconds
            'verify_ssl' => false, // Whether to verify SSL certificates (false for self-signed certs)
            'auth' => [
                'type' => 'none', // 'none', 'basic', 'bearer', 'query'
                'username' => null,
                'password' => null,
                'token' => null,
                'query_param' => null,
            ],
        ],
        'import_files' => true, // Whether to actually copy files or just metadata
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'msg', 'eml', 'zip', 'odt', 'tiff', 'tif'],
        'max_file_size' => 50 * 1024 * 1024, // 50MB max file size
        'preserve_original_names' => true, // Keep original file names when possible
        'missing_file_strategy' => 'warn', // 'warn', 'skip', or 'fail'
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Order
    |--------------------------------------------------------------------------
    |
    | Order in which entities should be imported to maintain referential integrity.
    |
    */
    'import_order' => [
        'users',
        'programs', // Though we're not importing these, we need to validate mappings
        'funders',
        'contracts',
        'report_due_dates',
        'due_date_emails',
        'due_date_email_assignments',
        'media', // Import media files last, after contracts exist
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Settings for import logging and reporting.
    |
    */
    'logging' => [
        'log_file' => 'legacy-import.log',
        'log_level' => 'info',
        'detailed_errors' => true,
        'save_failed_records' => true, // Save failed records to separate file for review
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Sync
    |--------------------------------------------------------------------------
    |
    | Settings for the scheduled `contracts:sync-legacy` command, which polls
    | the legacy contract database for new and changed contracts and brings
    | them into CAP TRACK without manual intervention.
    |
    */
    'auto_sync' => [
        // Master switch. When false the scheduled run is skipped entirely.
        'enabled' => env('LEGACY_AUTO_SYNC_ENABLED', true),

        // Import contracts that exist in legacy but have never been imported.
        'import_new' => env('LEGACY_AUTO_SYNC_IMPORT_NEW', true),

        // Re-sync contracts whose legacy record changed since our last sync.
        'update_existing' => env('LEGACY_AUTO_SYNC_UPDATE_EXISTING', true),

        // Download attachments for newly imported / changed contracts.
        'sync_media' => env('LEGACY_AUTO_SYNC_MEDIA', true),

        // Safety valve: refuse to touch more than this many contracts in one
        // run. A larger drift than this signals something unusual and should
        // be reviewed by hand rather than applied automatically.
        'max_per_run' => env('LEGACY_AUTO_SYNC_MAX_PER_RUN', 250),

        /*
        | The two clocks (this server and 10.55.0.10) are not guaranteed to be
        | in step. When comparing legacy `updated_at` against our
        | `last_synced_at` we subtract this many seconds from our timestamp so a
        | slightly-fast legacy clock cannot cause an edit to be missed forever.
        */
        'clock_skew_seconds' => env('LEGACY_AUTO_SYNC_CLOCK_SKEW', 120),

        /*
        |----------------------------------------------------------------------
        | Locally Owned Fields
        |----------------------------------------------------------------------
        |
        | Columns that CAP TRACK owns once a record exists here. The legacy
        | system has no equivalent concept, or ours has diverged deliberately,
        | so a re-sync must never write over them.
        |
        | `archived` / `date_archived` — archiving is a CAP TRACK-only workflow;
        |   the legacy `archived` column is unused (0 rows set).
        | `program_id` — resolved through fuzzy name matching on first import
        |   and frequently corrected by hand afterwards. Preserved when already
        |   set; still populated on update if it is NULL.
        | `service_code` — CAP TRACK / Sage Intacct concept with no legacy source.
        |
        */
        'locally_owned' => [
            'contract' => [
                'archived',
                'date_archived',
                'service_code',
                'program_id',
            ],

            /*
            | Due-date fields CAP TRACK owns. `status` is the important one:
            | legacy only tracks incomplete/completed, while CAP TRACK derives
            | pending/overdue/cancelled and links ContractReport submissions.
            | `reminder_intervals` is customised per due date here.
            */
            'due_date' => [
                'status',
                'reminder_intervals',
                'report_name',
                'report_type',
                'active',
                'reminder_sent',
                'reminder_sent_at',
            ],
        ],

        /*
        | Fields that should be filled on update only when the local value is
        | still empty. Lets a first-import gap get backfilled later without
        | overwriting a hand-entered correction.
        */
        'fill_if_empty' => [
            'contract' => [
                'program_id',
            ],
            'due_date' => [],
        ],

        /*
        |----------------------------------------------------------------------
        | Deleted-in-Legacy Handling
        |----------------------------------------------------------------------
        |
        | What to do when a contract we imported is later soft-deleted in the
        | legacy system. Supported: 'report' (flag it in the run summary and
        | change nothing) or 'archive' (set archived = true locally).
        | 'report' is the default: local ContractReports and attachments may
        | hang off those contracts and a legacy delete is often just cleanup.
        |
        */
        'deleted_in_legacy' => env('LEGACY_AUTO_SYNC_DELETED_STRATEGY', 'report'),

        /*
        |----------------------------------------------------------------------
        | Attribution
        |----------------------------------------------------------------------
        |
        | Scheduled runs have no authenticated user. Records touched by the
        | sync are attributed to the user with this email when one exists,
        | otherwise created_by / updated_by are left null.
        |
        */
        'system_user_email' => env('LEGACY_AUTO_SYNC_USER_EMAIL', null),

        /*
        |----------------------------------------------------------------------
        | Run Summary Notifications
        |----------------------------------------------------------------------
        |
        | Email addresses that receive a summary after a scheduled run. Set
        | `only_on_change` to false to receive a mail even on quiet nights.
        |
        */
        'notify' => [
            'recipients' => array_filter(
                array_map('trim', explode(',', (string) env('LEGACY_AUTO_SYNC_NOTIFY', '')))
            ),
            'only_on_change' => env('LEGACY_AUTO_SYNC_NOTIFY_ONLY_ON_CHANGE', true),
        ],
    ],
];
