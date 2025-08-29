<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncContractAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:sync-attachments {--dry-run : Preview changes without applying them} {--contract-id= : Sync specific contract only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contract attachments from legacy API';

    private $token;
    private $baseUrl;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $contractId = $this->option('contract-id');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Syncing contract attachments from legacy API...');
        
        try {
            // Initialize API connection
            $this->baseUrl = config('services.legacy_api.url');
            $this->authenticate();

            if ($contractId) {
                // Sync specific contract
                $contract = Contract::findOrFail($contractId);
                $this->syncSingleContract($contract, $dryRun);
            } else {
                // Sync all contracts
                $this->syncAllContracts($dryRun);
            }

            $this->info('Attachment sync completed successfully!');

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function authenticate()
    {
        $loginData = [
            'email' => config('services.legacy_api.email'),
            'password' => config('services.legacy_api.password'),
            'device_name' => config('services.legacy_api.device_name'),
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->post("{$this->baseUrl}/login", $loginData);

        if (!$response->successful()) {
            throw new \Exception('Failed to authenticate with legacy API: ' . $response->body());
        }

        $this->token = $response->json('token');
        $this->info('Authentication successful');
    }

    private function request()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout(30);
    }

    private function syncAllContracts($dryRun)
    {
        $contracts = Contract::whereNotNull('legacy_id')->get();
        $this->info("Found {$contracts->count()} contracts with legacy IDs");

        $progressBar = $this->output->createProgressBar($contracts->count());
        $progressBar->start();

        foreach ($contracts as $contract) {
            $this->syncSingleContract($contract, $dryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function syncSingleContract(Contract $contract, $dryRun)
    {
        if (!$contract->legacy_id) {
            $this->line("Skipping contract {$contract->id}: No legacy ID");
            return;
        }

        try {
            $this->line("Processing: {$contract->title} (Legacy ID: {$contract->legacy_id})");

            // Get contract with documents from legacy API
            $response = $this->request()->get("{$this->baseUrl}/contracts/{$contract->legacy_id}", [
                'include' => 'documents'
            ]);

            if (!$response->successful()) {
                $this->error("  Failed to get contract from API: " . $response->status());
                return;
            }

            $contractData = $response->json('data');

            if (!isset($contractData['documents']) || !is_array($contractData['documents'])) {
                $this->line("  No documents found");
                return;
            }

            $documents = $contractData['documents'];
            $this->info("  Found " . count($documents) . " documents");

            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($documents as $documentData) {
                // Check if document already exists
                $existingMedia = $contract->getMedia('documents')
                    ->where('file_name', $documentData['file_name'])
                    ->first();

                if ($existingMedia) {
                    $skippedCount++;
                    continue;
                }

                if (!$dryRun) {
                    try {
                        // Download the document
                        $documentResponse = $this->request()->get($documentData['url']);

                        if (!$documentResponse->successful()) {
                            $this->error("    Failed to download: {$documentData['name']}");
                            continue;
                        }

                        // Create temporary file
                        $tempPath = tempnam(sys_get_temp_dir(), 'contract_doc_');
                        file_put_contents($tempPath, $documentResponse->body());

                        // Add to media library
                        $mediaRecord = $contract->addMedia($tempPath)
                            ->usingName($documentData['name'])
                            ->usingFileName($documentData['file_name'])
                            ->withCustomProperties([
                                'legacy_document_id' => $documentData['id'],
                                'legacy_url' => $documentData['url'],
                                'original_size' => $documentData['size'],
                            ])
                            ->toMediaCollection('documents');

                        // Clean up temp file
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }

                        $this->line("    ✓ Added: {$documentData['name']}");
                        $syncedCount++;

                    } catch (\Exception $e) {
                        $this->error("    ✗ Failed to add {$documentData['name']}: " . $e->getMessage());
                    }
                } else {
                    $this->line("    [DRY RUN] Would add: {$documentData['name']}");
                    $syncedCount++;
                }
            }

            $this->line("  Result: {$syncedCount} synced, {$skippedCount} skipped");

        } catch (\Exception $e) {
            $this->error("  Error processing contract: " . $e->getMessage());
        }
    }
}
