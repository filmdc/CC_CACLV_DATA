<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\Funder;
use App\Models\Program;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LegacyContractsApiService
{
    private string $baseUrl;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.legacy_api.url');
        $this->authenticate();
    }

    /**
     * Authenticate with legacy API and cache token
     */
    private function authenticate(): void
    {
        $this->token = Cache::remember('legacy_api_token', 23 * 60 * 60, function () {
            $loginData = [
                'email' => config('services.legacy_api.email'),
                'password' => config('services.legacy_api.password'),
                'device_name' => config('services.legacy_api.device_name', 'New_Contracts_System'),
            ];

            Log::info('Attempting legacy API authentication', [
                'url' => "{$this->baseUrl}/login",
                'email' => $loginData['email'],
                'device_name' => $loginData['device_name']
            ]);

            $response = Http::timeout(30)
                ->acceptJson()
                ->contentType('application/json')
                ->post("{$this->baseUrl}/login", $loginData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Legacy API authentication successful', [
                    'user_id' => $data['user']['id'] ?? 'unknown',
                    'user_name' => $data['user']['name'] ?? 'unknown'
                ]);
                return $data['token'];
            }

            Log::error('Legacy API authentication failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $loginData
            ]);

            throw new \Exception('Failed to authenticate with legacy API: ' . $response->body());
        });
    }

    /**
     * Get HTTP client with authentication
     */
    private function request()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout(30)
            ->withOptions(['http_errors' => false]); // Don't throw on HTTP errors - handle manually
    }

    /**
     * Refresh authentication token
     */
    public function refreshToken(): void
    {
        Cache::forget('legacy_api_token');
        $this->authenticate();
    }

    /**
     * Sync all funders from legacy system
     */
    public function syncFunders(): bool
    {
        try {
            Log::info('Starting funder sync from legacy API');
            
            $response = $this->request()->get("{$this->baseUrl}/funders");
            
            if (!$response->successful()) {
                // Check for specific legacy system bugs
                $responseBody = $response->body();
                if (str_contains($responseBody, 'Method Illuminate\\Auth\\RequestGuard::loginUsingid does not exist')) {
                    throw new \Exception("LEGACY API BUG DETECTED: The legacy contract system has a bug with method name 'loginUsingid' (should be 'loginUsingId'). This needs to be fixed by the legacy system administrator. Response: {$responseBody}");
                }
                
                if (str_contains($responseBody, 'Property [id] does not exist on this collection instance')) {
                    throw new \Exception("LEGACY API BUG DETECTED: The FunderResource class is trying to access 'id' property on a collection instead of individual models. This is in /app/Http/Resources/Admin/FunderResource.php line 12. The legacy system administrator needs to fix this Laravel Resource bug. Response: {$responseBody}");
                }
                
                throw new \Exception("API request failed: {$response->status()} - {$responseBody}");
            }

            $funders = $response->json('data', []);
            $syncCount = 0;

            foreach ($funders as $funderData) {
                // Skip funders with null names (data issue in legacy system)
                if (empty($funderData['name'])) {
                    Log::warning("Skipping funder with null name", [
                        'legacy_id' => $funderData['id'],
                        'created_at' => $funderData['created_at'] ?? null
                    ]);
                    continue;
                }

                // First try to find existing funder by legacy_id
                $funder = Funder::where('legacy_id', $funderData['id'])->first();
                
                // If not found by legacy_id, try to match by name to avoid duplicates
                if (!$funder) {
                    $funder = Funder::where('name', $funderData['name'])->whereNull('legacy_id')->first();
                    if ($funder) {
                        Log::info("Matching existing funder by name", [
                            'existing_id' => $funder->id,
                            'name' => $funder->name,
                            'legacy_id' => $funderData['id']
                        ]);
                        // Update existing record with legacy ID
                        $funder->update([
                            'legacy_id' => $funderData['id'],
                            'description' => $funderData['description'] ?? $funder->description,
                            'last_synced_at' => now(),
                        ]);
                    } else {
                        // Create new funder if no match found
                        $funder = Funder::create([
                            'name' => $funderData['name'],
                            'description' => $funderData['description'] ?? null,
                            'active' => true,
                            'legacy_id' => $funderData['id'],
                            'last_synced_at' => now(),
                        ]);
                    }
                } else {
                    // Update existing funder found by legacy_id
                    $funder->update([
                        'name' => $funderData['name'],
                        'description' => $funderData['description'] ?? $funder->description,
                        'last_synced_at' => now(),
                    ]);
                }

                $syncCount++;
                Log::debug("Synced funder: {$funder->name} (Legacy ID: {$funderData['id']})");
            }

            Log::info("Funders sync completed successfully", ['count' => $syncCount]);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync funders from legacy API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync all programs from legacy system
     */
    public function syncPrograms(): bool
    {
        try {
            Log::info('Starting program sync from legacy API');
            
            $response = $this->request()->get("{$this->baseUrl}/programs");
            
            if (!$response->successful()) {
                throw new \Exception("API request failed: {$response->status()} - {$response->body()}");
            }

            $programs = $response->json('data', []);
            $syncCount = 0;

            foreach ($programs as $programData) {
                // Skip programs with null names (data issue in legacy system)
                if (empty($programData['name'])) {
                    Log::warning("Skipping program with null name", [
                        'legacy_id' => $programData['id'],
                        'created_at' => $programData['created_at'] ?? null
                    ]);
                    continue;
                }

                // First try to find existing program by legacy_id
                $program = Program::where('legacy_id', $programData['id'])->first();
                
                // If not found by legacy_id, try to match by name to avoid duplicates
                if (!$program) {
                    $program = Program::where('name', $programData['name'])->whereNull('legacy_id')->first();
                    if ($program) {
                        Log::info("Matching existing program by name", [
                            'existing_id' => $program->id,
                            'name' => $program->name,
                            'legacy_id' => $programData['id']
                        ]);
                        // Update existing record with legacy ID
                        $program->update([
                            'legacy_id' => $programData['id'],
                            'description' => $programData['description'] ?? $program->description,
                            'last_synced_at' => now(),
                        ]);
                    } else {
                        // Create new program if no match found
                        $program = Program::create([
                            'name' => $programData['name'],
                            'description' => $programData['description'] ?? null,
                            'active' => true,
                            'contract_management_only' => false, // Programs from legacy are regular programs
                            'legacy_id' => $programData['id'],
                            'last_synced_at' => now(),
                        ]);
                    }
                } else {
                    // Update existing program found by legacy_id
                    $program->update([
                        'name' => $programData['name'],
                        'description' => $programData['description'] ?? $program->description,
                        'last_synced_at' => now(),
                    ]);
                }

                $syncCount++;
                Log::debug("Synced program: {$program->name} (Legacy ID: {$programData['id']})");
            }

            Log::info("Programs sync completed successfully", ['count' => $syncCount]);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync programs from legacy API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync contracts from legacy system with pagination
     */
    public function syncContracts(int $page = 1): bool
    {
        try {
            Log::info("Starting contract sync from legacy API", ['page' => $page]);
            
            $response = $this->request()->get("{$this->baseUrl}/contracts", [
                'page' => $page,
                'per_page' => 100,
                'include' => 'programs,funder,reportDueDates'
            ]);
            
            if (!$response->successful()) {
                throw new \Exception("API request failed: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            $contracts = $data['data'] ?? [];
            $syncCount = 0;

            foreach ($contracts as $contractData) {
                $contract = $this->syncSingleContract($contractData);
                if ($contract) {
                    $syncCount++;
                }
            }

            // Check if there are more pages
            if (isset($data['meta']['last_page']) && $page < $data['meta']['last_page']) {
                $this->syncContracts($page + 1);
            }

            Log::info("Contracts sync completed for page {$page}", ['count' => $syncCount]);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync contracts from legacy API', [
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync a single contract from legacy data
     */
    private function syncSingleContract(array $contractData): ?Contract
    {
        try {
            // Skip contracts with null titles (data issue in legacy system)
            if (empty($contractData['title'])) {
                Log::warning("Skipping contract with null title", [
                    'legacy_id' => $contractData['id'],
                    'contract_number' => $contractData['contract_number'] ?? null,
                    'created_at' => $contractData['created_at'] ?? null
                ]);
                return null;
            }

            // Map legacy funder ID to current system
            $funderId = null;
            if (isset($contractData['funder']['id'])) {
                $funder = Funder::where('legacy_id', $contractData['funder']['id'])->first();
                $funderId = $funder?->id;
                
                // Log if funder not found but don't fail the contract sync
                if (!$funder) {
                    Log::warning("Legacy funder not found in current system", [
                        'contract_legacy_id' => $contractData['id'],
                        'contract_title' => $contractData['title'],
                        'legacy_funder_id' => $contractData['funder']['id'],
                        'legacy_funder_name' => $contractData['funder']['name'] ?? 'null'
                    ]);
                }
            }

            // Create/update the contract
            $contract = Contract::updateOrCreate(
                ['legacy_id' => $contractData['id']],
                [
                    'title' => $contractData['title'],
                    'contract_number' => $contractData['contract_number'],
                    'cfda_number' => $contractData['cfda_number'] ?? null,
                    'purpose' => $contractData['purpose'],
                    'date_of_award' => $contractData['date_of_award'] ?? null,
                    'amount' => $contractData['amount'],
                    'start_date' => $contractData['start_date'] ?? null,
                    'end_date' => $contractData['end_date'] ?? null,
                    'fund_code' => $contractData['grant_code'] ?? null, // Map grant_code -> fund_code
                    'notes' => $contractData['notes'],
                    'archived' => $contractData['archived'] === 'yes' || $contractData['archived'] === true, // Handle string "yes" from legacy
                    'date_archived' => $contractData['date_archived'] ?? null,
                    'funder_id' => $funderId,
                    'last_synced_at' => now(),
                ]
            );

            // Sync many-to-many programs relationship
            if (isset($contractData['programs']) && is_array($contractData['programs'])) {
                $this->syncContractPrograms($contract, $contractData['programs']);
            }

            // Sync report due dates
            if (isset($contractData['reportDueDates']) && is_array($contractData['reportDueDates'])) {
                $this->syncReportDueDates($contract, $contractData['reportDueDates']);
            }

            Log::debug("Synced contract: {$contract->title} (Legacy ID: {$contractData['id']})");
            return $contract;

        } catch (\Exception $e) {
            Log::error('Failed to sync single contract', [
                'legacy_id' => $contractData['id'] ?? 'unknown',
                'title' => $contractData['title'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Sync contract programs many-to-many relationship
     */
    private function syncContractPrograms(Contract $contract, array $programsData): void
    {
        $programIds = [];
        
        foreach ($programsData as $programData) {
            $program = Program::where('legacy_id', $programData['id'])->first();
            if ($program) {
                $programIds[] = $program->id;
            }
        }
        
        $contract->programs()->sync($programIds);
        Log::debug("Synced {count} programs for contract {contract}", [
            'count' => count($programIds),
            'contract' => $contract->title
        ]);
    }

    /**
     * Sync report due dates for a contract
     */
    private function syncReportDueDates(Contract $contract, array $dueDatesData): void
    {
        foreach ($dueDatesData as $dueDateData) {
            try {
                $reportDueDate = ContractReportDueDate::updateOrCreate(
                    [
                        'legacy_id' => $dueDateData['id'],
                        'contract_id' => $contract->id
                    ],
                    [
                        'report_type' => $dueDateData['report_type'],
                        'report_name' => $dueDateData['report_name'],
                        'due_date' => $dueDateData['due_date'],
                        'due_time' => $dueDateData['time'] ?? null,
                        'description' => $dueDateData['report_name'], // Use report_name as description fallback
                        'first_reminder' => $dueDateData['first_reminder'] ?? null,
                        'second_reminder' => $dueDateData['second_reminder'] ?? null,
                        'final_reminder' => $dueDateData['final_reminder'] ?? null,
                        'invite_sent' => $dueDateData['invite_sent'] ?? false,
                        'is_recurring' => $dueDateData['is_recurring'] ?? false,
                        'recurrence_pattern' => $dueDateData['recurrence_pattern'] ?? null,
                        'reminder_emails' => $dueDateData['reminder_emails'] ?? null,
                        'microsoft_event_id' => $dueDateData['microsoft_event_id'] ?? null,
                        'active' => true,
                        'status' => 'pending', // Default status
                        'last_synced_at' => now(),
                    ]
                );

                // Convert individual reminder fields to array for current system compatibility
                if ($reportDueDate) {
                    $reportDueDate->setLegacyReminders(
                        $dueDateData['first_reminder'] ?? null,
                        $dueDateData['second_reminder'] ?? null,
                        $dueDateData['final_reminder'] ?? null
                    );
                    $reportDueDate->save();
                }

            } catch (\Exception $e) {
                Log::error('Failed to sync report due date', [
                    'contract_id' => $contract->id,
                    'legacy_due_date_id' => $dueDateData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get a specific contract from legacy API
     */
    public function getContract(int $legacyId): ?array
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/contracts/{$legacyId}");
            
            if ($response->successful()) {
                return $response->json('data');
            }
            
            Log::warning("Contract not found in legacy API", ['legacy_id' => $legacyId]);
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
     * Full sync of all data types
     */
    public function fullSync(): bool
    {
        try {
            Log::info('Starting full sync from legacy API');
            
            // Sync in dependency order
            $this->syncFunders();
            $this->syncPrograms();
            $this->syncContracts();
            
            Log::info('Full sync completed successfully');
            return true;

        } catch (\Exception $e) {
            Log::error('Full sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}