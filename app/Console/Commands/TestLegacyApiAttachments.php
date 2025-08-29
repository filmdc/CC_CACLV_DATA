<?php

namespace App\Console\Commands;

use App\Services\LegacyContractsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestLegacyApiAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:test-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test legacy API for contract attachment data structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing legacy API for contract attachment data...');
        
        try {
            // Use the existing service to authenticate
            $service = new LegacyContractsApiService();
            
            // Test direct API calls
            $baseUrl = config('services.legacy_api.url');
            
            // Get token manually for testing
            $loginData = [
                'email' => config('services.legacy_api.email'),
                'password' => config('services.legacy_api.password'),
                'device_name' => config('services.legacy_api.device_name'),
            ];

            $this->info('Authenticating with legacy API...');
            $loginResponse = Http::timeout(30)
                ->acceptJson()
                ->post("{$baseUrl}/login", $loginData);

            if (!$loginResponse->successful()) {
                $this->error('Authentication failed: ' . $loginResponse->body());
                return Command::FAILURE;
            }

            $token = $loginResponse->json('token');
            $this->info('Authentication successful');

            // Test contracts endpoint
            $this->info('Testing contracts endpoint...');
            $contractsResponse = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$baseUrl}/contracts", ['per_page' => 5]);

            if (!$contractsResponse->successful()) {
                $this->error('Contracts request failed: ' . $contractsResponse->body());
                return Command::FAILURE;
            }

            $contractsData = $contractsResponse->json();
            
            if (isset($contractsData['data']) && !empty($contractsData['data'])) {
                $this->info('Found contracts. Analyzing structure...');
                
                foreach ($contractsData['data'] as $contract) {
                    $this->line("Contract: {$contract['title']} (ID: {$contract['id']})");
                    $this->line("  Available keys: " . implode(', ', array_keys($contract)));
                    
                    // Check for attachment-related keys
                    $attachmentKeys = [];
                    foreach (array_keys($contract) as $key) {
                        if (stripos($key, 'attachment') !== false || 
                            stripos($key, 'document') !== false || 
                            stripos($key, 'file') !== false ||
                            stripos($key, 'media') !== false) {
                            $attachmentKeys[] = $key;
                        }
                    }
                    
                    if (!empty($attachmentKeys)) {
                        $this->info("  Attachment-related keys: " . implode(', ', $attachmentKeys));
                        
                        foreach ($attachmentKeys as $key) {
                            if (is_array($contract[$key])) {
                                $this->info("  {$key}: " . count($contract[$key]) . " items");
                                if (!empty($contract[$key])) {
                                    $this->line("    First item keys: " . implode(', ', array_keys($contract[$key][0])));
                                    
                                    // Show details of first few documents
                                    foreach (array_slice($contract[$key], 0, 3) as $i => $doc) {
                                        $this->line("    Document " . ($i + 1) . ":");
                                        $this->line("      ID: {$doc['id']}, Name: {$doc['name']}");
                                        $this->line("      File: {$doc['file_name']}, Size: " . number_format($doc['size']) . " bytes");
                                        $this->line("      URL: {$doc['url']}");
                                    }
                                }
                            } else {
                                $this->info("  {$key}: " . ($contract[$key] ?: 'null'));
                            }
                        }
                    } else {
                        $this->line("  No attachment-related keys found");
                    }
                    
                    // Test specific contract endpoint
                    $this->info("  Testing individual contract endpoint...");
                    $singleContractResponse = Http::withToken($token)
                        ->acceptJson()
                        ->timeout(30)
                        ->get("{$baseUrl}/contracts/{$contract['id']}");
                    
                    if ($singleContractResponse->successful()) {
                        $singleContract = $singleContractResponse->json('data');
                        $singleKeys = array_keys($singleContract);
                        $newKeys = array_diff($singleKeys, array_keys($contract));
                        
                        if (!empty($newKeys)) {
                            $this->info("  Additional keys in single contract view: " . implode(', ', $newKeys));
                        } else {
                            $this->line("  No additional keys in single contract view");
                        }
                    } else {
                        $this->error("  Failed to get single contract: " . $singleContractResponse->body());
                    }
                    
                    $this->newLine();
                    break; // Only test first contract
                }
            } else {
                $this->error('No contract data found');
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
