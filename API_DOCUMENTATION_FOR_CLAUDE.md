# API Integration Guide for Claude - Legacy Contracts Database

## Overview
This document is designed to help another Claude instance integrate a newer Laravel project with this legacy contracts database API. The API provides read/write access to contracts, programs, funders, reports, and related data.

## Important Context
- **Legacy Server**: This is the source of truth for contract data
- **New Server Location**: 10.55.0.43 (the server you'll be implementing on)
- **Authentication**: Laravel Sanctum token-based auth
- **Data Format**: JSON API responses with nested relationships

## Initial Setup Requirements

### 1. Environment Configuration
Add these to your new Laravel project's `.env` file:
```env
LEGACY_API_URL=http://[legacy-server-ip]/api/v1
LEGACY_API_EMAIL=your-api-user@example.com
LEGACY_API_PASSWORD=your-api-password
LEGACY_API_DEVICE_NAME=New_Contracts_System
```

### 2. Create API Client Service
Create a service class to handle all legacy API communications:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LegacyContractsApiService
{
    private $baseUrl;
    private $token;
    
    public function __construct()
    {
        $this->baseUrl = config('services.legacy_api.url');
        $this->authenticate();
    }
    
    private function authenticate()
    {
        // Cache token for 23 hours (tokens don't expire in legacy system)
        $this->token = Cache::remember('legacy_api_token', 23 * 60 * 60, function () {
            $response = Http::post("{$this->baseUrl}/login", [
                'email' => config('services.legacy_api.email'),
                'password' => config('services.legacy_api.password'),
                'device_name' => config('services.legacy_api.device_name'),
            ]);
            
            if ($response->successful()) {
                return $response->json('token');
            }
            
            throw new \Exception('Failed to authenticate with legacy API');
        });
    }
    
    private function request()
    {
        return Http::withToken($this->token)
                   ->acceptJson()
                   ->timeout(30);
    }
    
    public function refreshToken()
    {
        Cache::forget('legacy_api_token');
        $this->authenticate();
    }
    
    // Implement specific methods below...
}
```

## Data Models and Schema Mapping

### 1. Contracts Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'contract_number' => string,
    'date_of_award' => date (Y-m-d),
    'amount' => decimal(15,2),
    'start_date' => date,
    'end_date' => date,
    'purpose' => text,
    'grant_code' => string,
    'notes' => text,
    'title' => string,
    'cfda_number' => string,
    'archived' => boolean,
    'date_archived' => date|null,
    'funder_id' => integer,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
    
    // Relationships
    'programs' => array of Program objects,
    'funder' => Funder object,
    'reportDueDates' => array of ReportDueDate objects,
    'documents' => array of media objects
]
```

**Sync Method**:
```php
public function syncContracts()
{
    $response = $this->request()->get("{$this->baseUrl}/contracts");
    
    if ($response->successful()) {
        $contracts = $response->json('data');
        
        foreach ($contracts as $contractData) {
            // Map legacy fields to your schema
            $mappedData = [
                'legacy_id' => $contractData['id'],
                'contract_number' => $contractData['contract_number'],
                'award_date' => $contractData['date_of_award'],
                'amount' => $contractData['amount'],
                'start_date' => $contractData['start_date'],
                'end_date' => $contractData['end_date'],
                'purpose' => $contractData['purpose'],
                'grant_code' => $contractData['grant_code'] ?? null,
                'notes' => $contractData['notes'],
                'title' => $contractData['title'],
                'cfda_number' => $contractData['cfda_number'] ?? null,
                'is_archived' => $contractData['archived'] ?? false,
                'archived_date' => $contractData['date_archived'],
                'legacy_funder_id' => $contractData['funder']['id'] ?? null,
                'last_synced_at' => now(),
            ];
            
            // Upsert based on legacy_id
            Contract::updateOrCreate(
                ['legacy_id' => $contractData['id']],
                $mappedData
            );
            
            // Sync relationships
            if (isset($contractData['programs'])) {
                $this->syncContractPrograms($contractData['id'], $contractData['programs']);
            }
        }
    }
}
```

### 2. Programs Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'name' => string (required),
    'description' => text|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
]
```

### 3. Funders Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'name' => string (required),
    'description' => text|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
]
```

### 4. Report Due Dates Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'contract_id' => integer,
    'report_type' => string,
    'report_name' => string,
    'due_date' => date,
    'time' => time (H:i:s),
    'first_reminder' => integer|null,
    'second_reminder' => integer|null,
    'final_reminder' => integer|null,
    'invite_sent' => boolean,
    'is_recurring' => boolean,
    'recurrence_pattern' => string|null,
    'reminder_emails' => text|null,
    'created_by' => string|null,
    'updated_by' => string|null,
    'microsoft_event_id' => string|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
]
```

### 5. Users Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'name' => string,
    'email' => string,
    'email_verified_at' => timestamp|null,
    'approved' => boolean (default: 0),
    'verified' => boolean (default: 0),
    'verified_at' => timestamp|null,
    'verification_token' => string|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
    
    // Relationships
    'roles' => array with permissions,
    'programs' => array of assigned programs
]
```

**Important User Notes**:
- Users must be `approved = 1` to authenticate
- Users can be assigned to specific programs (many-to-many)
- Roles include nested permissions

### 6. Reports Model
**Legacy Schema**:
```php
[
    'id' => integer,
    'name' => string,
    'description' => text|null,
    'status' => string|null,
    'submitted_at' => timestamp|null,
    'due_date' => date|null,
    'report_due_date_id' => integer|null,
    'submitted_by_id' => integer|null,
    'approved_by_id' => integer|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
    'deleted_at' => timestamp|null,
    
    // Media attachments
    'documents' => array of media objects
]
```

## Complete Implementation Example

### 1. Service Provider Configuration
```php
// config/services.php
'legacy_api' => [
    'url' => env('LEGACY_API_URL'),
    'email' => env('LEGACY_API_EMAIL'),
    'password' => env('LEGACY_API_PASSWORD'),
    'device_name' => env('LEGACY_API_DEVICE_NAME', 'New_Contracts_System'),
],
```

### 2. Full Service Implementation
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Contract;
use App\Models\Program;
use App\Models\Funder;
use App\Models\ReportDueDate;
use App\Models\User;

class LegacyContractsApiService
{
    private $baseUrl;
    private $token;
    
    public function __construct()
    {
        $this->baseUrl = config('services.legacy_api.url');
        $this->authenticate();
    }
    
    private function authenticate()
    {
        $this->token = Cache::remember('legacy_api_token', 23 * 60 * 60, function () {
            $response = Http::post("{$this->baseUrl}/login", [
                'email' => config('services.legacy_api.email'),
                'password' => config('services.legacy_api.password'),
                'device_name' => config('services.legacy_api.device_name'),
            ]);
            
            if ($response->successful()) {
                return $response->json('token');
            }
            
            Log::error('Legacy API authentication failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to authenticate with legacy API');
        });
    }
    
    private function request()
    {
        return Http::withToken($this->token)
                   ->acceptJson()
                   ->timeout(30)
                   ->retry(3, 100)
                   ->withOptions(['verify' => false]); // Only if legacy uses self-signed cert
    }
    
    /**
     * Sync all funders from legacy system
     */
    public function syncFunders()
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/funders");
            
            if ($response->successful()) {
                $funders = $response->json('data');
                
                foreach ($funders as $funderData) {
                    Funder::updateOrCreate(
                        ['legacy_id' => $funderData['id']],
                        [
                            'name' => $funderData['name'],
                            'description' => $funderData['description'],
                            'last_synced_at' => now(),
                        ]
                    );
                }
                
                Log::info('Funders synced successfully', ['count' => count($funders)]);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync funders', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Sync all programs from legacy system
     */
    public function syncPrograms()
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/programs");
            
            if ($response->successful()) {
                $programs = $response->json('data');
                
                foreach ($programs as $programData) {
                    Program::updateOrCreate(
                        ['legacy_id' => $programData['id']],
                        [
                            'name' => $programData['name'],
                            'description' => $programData['description'],
                            'last_synced_at' => now(),
                        ]
                    );
                }
                
                Log::info('Programs synced successfully', ['count' => count($programs)]);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync programs', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Sync contracts with pagination support
     */
    public function syncContracts($page = 1)
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/contracts", [
                'page' => $page,
                'per_page' => 100,
                'include' => 'programs,funder,reportDueDates'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $contracts = $data['data'] ?? [];
                
                foreach ($contracts as $contractData) {
                    $contract = Contract::updateOrCreate(
                        ['legacy_id' => $contractData['id']],
                        [
                            'contract_number' => $contractData['contract_number'],
                            'award_date' => $contractData['date_of_award'],
                            'amount' => $contractData['amount'],
                            'start_date' => $contractData['start_date'],
                            'end_date' => $contractData['end_date'],
                            'purpose' => $contractData['purpose'],
                            'grant_code' => $contractData['grant_code'],
                            'notes' => $contractData['notes'],
                            'title' => $contractData['title'],
                            'cfda_number' => $contractData['cfda_number'],
                            'is_archived' => $contractData['archived'] ?? false,
                            'archived_date' => $contractData['date_archived'],
                            'legacy_funder_id' => $contractData['funder']['id'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );
                    
                    // Sync programs relationship
                    if (isset($contractData['programs'])) {
                        $programIds = collect($contractData['programs'])
                            ->pluck('id')
                            ->map(function ($legacyId) {
                                $program = Program::where('legacy_id', $legacyId)->first();
                                return $program ? $program->id : null;
                            })
                            ->filter()
                            ->toArray();
                        
                        $contract->programs()->sync($programIds);
                    }
                    
                    // Sync report due dates
                    if (isset($contractData['reportDueDates'])) {
                        $this->syncReportDueDates($contract, $contractData['reportDueDates']);
                    }
                }
                
                // Check if there are more pages
                if (isset($data['meta']['last_page']) && $page < $data['meta']['last_page']) {
                    $this->syncContracts($page + 1);
                }
                
                Log::info('Contracts synced successfully', [
                    'page' => $page,
                    'count' => count($contracts)
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync contracts', [
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Sync report due dates for a contract
     */
    private function syncReportDueDates($contract, $dueDatesData)
    {
        foreach ($dueDatesData as $dueDateData) {
            ReportDueDate::updateOrCreate(
                [
                    'legacy_id' => $dueDateData['id'],
                    'contract_id' => $contract->id
                ],
                [
                    'report_type' => $dueDateData['report_type'],
                    'report_name' => $dueDateData['report_name'],
                    'due_date' => $dueDateData['due_date'],
                    'due_time' => $dueDateData['time'],
                    'first_reminder_days' => $dueDateData['first_reminder'],
                    'second_reminder_days' => $dueDateData['second_reminder'],
                    'final_reminder_days' => $dueDateData['final_reminder'],
                    'invite_sent' => $dueDateData['invite_sent'] ?? false,
                    'is_recurring' => $dueDateData['is_recurring'] ?? false,
                    'recurrence_pattern' => $dueDateData['recurrence_pattern'],
                    'reminder_emails' => $dueDateData['reminder_emails'],
                    'microsoft_event_id' => $dueDateData['microsoft_event_id'],
                    'last_synced_at' => now(),
                ]
            );
        }
    }
    
    /**
     * Get a specific contract from legacy API
     */
    public function getContract($legacyId)
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/contracts/{$legacyId}");
            
            if ($response->successful()) {
                return $response->json('data');
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get contract from legacy API', [
                'legacy_id' => $legacyId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Create a new contract in legacy system
     */
    public function createContract(array $data)
    {
        try {
            // Map your fields to legacy fields
            $legacyData = [
                'contract_number' => $data['contract_number'],
                'date_of_award' => $data['award_date'],
                'amount' => $data['amount'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'purpose' => $data['purpose'],
                'grant_code' => $data['grant_code'] ?? null,
                'notes' => $data['notes'] ?? null,
                'title' => $data['title'],
                'cfda_number' => $data['cfda_number'] ?? null,
                'funder_id' => $this->getFunderLegacyId($data['funder_id']),
                'programs' => $this->getProgramLegacyIds($data['program_ids'] ?? []),
            ];
            
            $response = $this->request()->post("{$this->baseUrl}/contracts", $legacyData);
            
            if ($response->successful()) {
                $createdContract = $response->json('data');
                
                // Store the legacy ID in your database
                Contract::where('id', $data['id'])->update([
                    'legacy_id' => $createdContract['id']
                ]);
                
                return $createdContract;
            }
            
            Log::error('Failed to create contract in legacy system', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to create contract in legacy API', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Download document from legacy system
     */
    public function downloadDocument($contractLegacyId, $mediaId)
    {
        try {
            $contract = $this->getContract($contractLegacyId);
            
            if ($contract && isset($contract['documents'])) {
                $document = collect($contract['documents'])->firstWhere('id', $mediaId);
                
                if ($document) {
                    $response = Http::withToken($this->token)
                                   ->get($document['url']);
                    
                    if ($response->successful()) {
                        return [
                            'content' => $response->body(),
                            'mime_type' => $document['mime_type'],
                            'filename' => $document['file_name'],
                        ];
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to download document', [
                'contract_legacy_id' => $contractLegacyId,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    // Helper methods
    private function getFunderLegacyId($funderId)
    {
        $funder = Funder::find($funderId);
        return $funder ? $funder->legacy_id : null;
    }
    
    private function getProgramLegacyIds(array $programIds)
    {
        return Program::whereIn('id', $programIds)
                     ->pluck('legacy_id')
                     ->toArray();
    }
}
```

### 3. Sync Command Implementation
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LegacyContractsApiService;

class SyncLegacyContractsCommand extends Command
{
    protected $signature = 'legacy:sync {--type=all : Type to sync (all|funders|programs|contracts)}';
    protected $description = 'Sync data from legacy contracts database';
    
    public function handle(LegacyContractsApiService $apiService)
    {
        $type = $this->option('type');
        
        $this->info("Starting sync process for: {$type}");
        
        try {
            switch ($type) {
                case 'funders':
                    $apiService->syncFunders();
                    break;
                    
                case 'programs':
                    $apiService->syncPrograms();
                    break;
                    
                case 'contracts':
                    $apiService->syncContracts();
                    break;
                    
                case 'all':
                    // Sync in correct order due to dependencies
                    $this->info('Syncing funders...');
                    $apiService->syncFunders();
                    
                    $this->info('Syncing programs...');
                    $apiService->syncPrograms();
                    
                    $this->info('Syncing contracts...');
                    $apiService->syncContracts();
                    break;
                    
                default:
                    $this->error("Unknown sync type: {$type}");
                    return 1;
            }
            
            $this->info('Sync completed successfully!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            return 1;
        }
    }
}
```

### 4. Migration Examples for New System
```php
// Create legacy ID columns in your migrations
Schema::table('contracts', function (Blueprint $table) {
    $table->unsignedBigInteger('legacy_id')->nullable()->unique();
    $table->unsignedBigInteger('legacy_funder_id')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    
    $table->index('legacy_id');
});

Schema::table('programs', function (Blueprint $table) {
    $table->unsignedBigInteger('legacy_id')->nullable()->unique();
    $table->timestamp('last_synced_at')->nullable();
});

Schema::table('funders', function (Blueprint $table) {
    $table->unsignedBigInteger('legacy_id')->nullable()->unique();
    $table->timestamp('last_synced_at')->nullable();
});

Schema::table('report_due_dates', function (Blueprint $table) {
    $table->unsignedBigInteger('legacy_id')->nullable()->unique();
    $table->timestamp('last_synced_at')->nullable();
});
```

## Error Handling and Monitoring

### 1. Handle API Failures Gracefully
```php
// In your controller or service
try {
    $contract = $this->legacyApi->getContract($legacyId);
    
    if (!$contract) {
        // Fall back to local data
        $localContract = Contract::where('legacy_id', $legacyId)->first();
        if ($localContract) {
            Log::warning('Using cached contract data', ['legacy_id' => $legacyId]);
            return $localContract;
        }
    }
} catch (\Exception $e) {
    // Log error but don't crash the application
    Log::error('Legacy API error', [
        'action' => 'getContract',
        'legacy_id' => $legacyId,
        'error' => $e->getMessage()
    ]);
    
    // Use cached data if available
    return Contract::where('legacy_id', $legacyId)->first();
}
```

### 2. Schedule Regular Syncs
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sync funders and programs daily
    $schedule->command('legacy:sync --type=funders')
             ->daily()
             ->at('02:00')
             ->withoutOverlapping()
             ->onFailure(function () {
                 Log::error('Failed to sync funders from legacy system');
             });
    
    $schedule->command('legacy:sync --type=programs')
             ->daily()
             ->at('02:30')
             ->withoutOverlapping();
    
    // Sync contracts every 4 hours
    $schedule->command('legacy:sync --type=contracts')
             ->everyFourHours()
             ->withoutOverlapping();
}
```

## Important Considerations

1. **Rate Limiting**: The legacy API has a rate limit of 60 requests per minute
2. **Authentication**: Tokens don't expire in the legacy system, but implement token refresh logic
3. **Soft Deletes**: Legacy system uses soft deletes - deleted_at field
4. **Media Files**: Documents are handled via Spatie Media Library - URLs provided in API responses
5. **Permissions**: API respects Laravel Gates - ensure your API user has proper permissions
6. **CORS**: Already configured for 10.55.0.43
7. **Timestamps**: All dates/times in Y-m-d H:i:s format

## Testing the Integration

```php
// Quick test in tinker
$api = new \App\Services\LegacyContractsApiService();
$api->syncFunders(); // Should return true
$api->getContract(1); // Should return contract data
```

## Troubleshooting

1. **401 Unauthorized**: Check API credentials and user approval status
2. **403 Forbidden**: User lacks required permissions (check Gates)
3. **Connection errors**: Verify network connectivity between servers
4. **Data mismatch**: Check field mapping in sync methods
5. **Performance issues**: Implement pagination for large datasets

This implementation provides a robust foundation for syncing data between the new and legacy systems while handling errors gracefully and maintaining data consistency.