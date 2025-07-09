<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContractDataMappingService
{
    protected array $config;
    protected array $programCache = [];
    protected array $funderMappingCache = [];

    public function __construct()
    {
        $this->config = config('legacy-import');
    }

    /**
     * Map old system program name to new system program ID
     */
    public function mapProgram(string $oldProgramName): ?int
    {
        // Check cache first
        if (isset($this->programCache[$oldProgramName])) {
            return $this->programCache[$oldProgramName];
        }

        // Check configured mappings
        $programMapping = $this->config['program_mapping'] ?? [];
        
        if (isset($programMapping[$oldProgramName])) {
            $programId = $programMapping[$oldProgramName];
            
            // Validate that the program exists
            $program = Program::find($programId);
            if ($program) {
                $this->programCache[$oldProgramName] = $programId;
                return $programId;
            } else {
                Log::warning("Mapped program ID {$programId} for '{$oldProgramName}' does not exist");
            }
        }

        // Try to find by name match (case-insensitive)
        $program = Program::whereRaw('LOWER(name) = ?', [strtolower($oldProgramName)])->first();
        if ($program) {
            $this->programCache[$oldProgramName] = $program->id;
            Log::info("Auto-mapped program '{$oldProgramName}' to '{$program->name}' (ID: {$program->id})");
            return $program->id;
        }

        // Try partial name matching for common variations
        $program = $this->findProgramByPartialMatch($oldProgramName);
        if ($program) {
            $this->programCache[$oldProgramName] = $program->id;
            Log::info("Partial-matched program '{$oldProgramName}' to '{$program->name}' (ID: {$program->id})");
            return $program->id;
        }

        // Use default program if configured
        $defaultProgramId = $this->config['default_program_id'] ?? null;
        if ($defaultProgramId) {
            $defaultProgram = Program::find($defaultProgramId);
            if ($defaultProgram) {
                $this->programCache[$oldProgramName] = $defaultProgramId;
                Log::warning("Using default program for '{$oldProgramName}': {$defaultProgram->name} (ID: {$defaultProgramId})");
                return $defaultProgramId;
            }
        }

        // No mapping found
        $this->programCache[$oldProgramName] = null;
        Log::error("No program mapping found for: {$oldProgramName}");
        return null;
    }

    /**
     * Find program by partial name matching
     */
    protected function findProgramByPartialMatch(string $oldProgramName): ?Program
    {
        $normalizedOldName = $this->normalizeString($oldProgramName);
        
        // Common program name variations and abbreviations
        $commonMappings = [
            'energy' => ['energy', 'liheap', 'heap', 'assistance'],
            'csbg' => ['csbg', 'community services', 'block grant'],
            'weatherization' => ['weatherization', 'wx', 'weather'],
            'head start' => ['head start', 'hs', 'early childhood'],
            'emergency' => ['emergency', 'crisis', 'urgent'],
            'housing' => ['housing', 'shelter', 'homeless'],
            'transportation' => ['transportation', 'transport', 'transit'],
            'multi' => ['multi', 'multiple', 'various', 'general'],
        ];

        $programs = Program::active()->get();
        
        foreach ($programs as $program) {
            $normalizedProgramName = $this->normalizeString($program->name);
            
            // Direct partial match
            if (str_contains($normalizedProgramName, $normalizedOldName) || 
                str_contains($normalizedOldName, $normalizedProgramName)) {
                return $program;
            }

            // Check common mappings
            foreach ($commonMappings as $keywords) {
                $oldMatches = false;
                $newMatches = false;
                
                foreach ($keywords as $keyword) {
                    if (str_contains($normalizedOldName, $keyword)) {
                        $oldMatches = true;
                    }
                    if (str_contains($normalizedProgramName, $keyword)) {
                        $newMatches = true;
                    }
                }
                
                if ($oldMatches && $newMatches) {
                    return $program;
                }
            }
        }

        return null;
    }

    /**
     * Map funder name with optional standardization
     */
    public function mapFunderName(?string $oldFunderName): string
    {
        // Handle null values
        if (empty($oldFunderName)) {
            return 'Unknown Funder';
        }
        
        // Check cache first
        if (isset($this->funderMappingCache[$oldFunderName])) {
            return $this->funderMappingCache[$oldFunderName];
        }

        $funderMapping = $this->config['funder_mapping'] ?? [];
        
        // Check configured mappings
        if (isset($funderMapping[$oldFunderName])) {
            $mappedName = $funderMapping[$oldFunderName];
            $this->funderMappingCache[$oldFunderName] = $mappedName;
            return $mappedName;
        }

        // Apply automatic standardization
        $standardizedName = $this->standardizeFunderName($oldFunderName);
        $this->funderMappingCache[$oldFunderName] = $standardizedName;
        
        return $standardizedName;
    }

    /**
     * Standardize funder names
     */
    protected function standardizeFunderName(string $funderName): string
    {
        // Common standardizations
        $standardizations = [
            // Government entities
            'US Dept' => 'U.S. Department',
            'US Department' => 'U.S. Department',
            'United States Dept' => 'U.S. Department',
            'United States Department' => 'U.S. Department',
            'Dept of' => 'Department of',
            'DOE' => 'Department of Energy',
            'HHS' => 'Department of Health and Human Services',
            'DOL' => 'Department of Labor',
            'USDA' => 'U.S. Department of Agriculture',
            'HUD' => 'Department of Housing and Urban Development',
            
            // State standardizations
            'State of Nevada' => 'State of Nevada',
            'Nevada State' => 'State of Nevada',
            'NV State' => 'State of Nevada',
            
            // County standardizations
            'Clark Co' => 'Clark County',
            'Clark County' => 'Clark County, Nevada',
            'Washoe Co' => 'Washoe County',
            'Washoe County' => 'Washoe County, Nevada',
        ];

        $standardizedName = $funderName;
        
        foreach ($standardizations as $pattern => $replacement) {
            $standardizedName = str_ireplace($pattern, $replacement, $standardizedName);
        }

        // Clean up extra spaces and standardize formatting
        $standardizedName = preg_replace('/\s+/', ' ', trim($standardizedName));
        
        return $standardizedName;
    }

    /**
     * Normalize string for comparison
     */
    protected function normalizeString(string $input): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $input)));
    }

    /**
     * Get all available programs for mapping reference
     */
    public function getAvailablePrograms(): array
    {
        return Cache::remember('import_available_programs', 3600, function () {
            return Program::active()
                ->orderBy('name')
                ->get(['id', 'name', 'contract_management_only'])
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->name,
                        'contract_only' => $program->contract_management_only,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Validate that all required program mappings exist
     */
    public function validateProgramMappings(array $oldProgramNames): array
    {
        $validation = [
            'valid' => [],
            'invalid' => [],
            'using_default' => [],
        ];

        foreach (array_unique($oldProgramNames) as $oldProgramName) {
            $programId = $this->mapProgram($oldProgramName);
            
            if ($programId) {
                if ($programId === $this->config['default_program_id']) {
                    $validation['using_default'][] = $oldProgramName;
                } else {
                    $validation['valid'][] = $oldProgramName;
                }
            } else {
                $validation['invalid'][] = $oldProgramName;
            }
        }

        return $validation;
    }

    /**
     * Generate program mapping suggestions
     */
    public function generateProgramMappingSuggestions(array $oldProgramNames): array
    {
        $suggestions = [];
        $availablePrograms = $this->getAvailablePrograms();

        foreach (array_unique($oldProgramNames) as $oldProgramName) {
            // Skip if already mapped
            if (isset($this->config['program_mapping'][$oldProgramName])) {
                continue;
            }

            $normalizedOldName = $this->normalizeString($oldProgramName);
            $matches = [];

            foreach ($availablePrograms as $program) {
                $normalizedNewName = $this->normalizeString($program['name']);
                
                // Calculate similarity score
                $similarity = 0;
                similar_text($normalizedOldName, $normalizedNewName, $similarity);
                
                if ($similarity > 50) {
                    $matches[] = [
                        'program' => $program,
                        'similarity' => $similarity,
                    ];
                }
            }

            // Sort by similarity
            usort($matches, function ($a, $b) {
                return $b['similarity'] - $a['similarity'];
            });

            $suggestions[$oldProgramName] = array_slice($matches, 0, 3); // Top 3 matches
        }

        return $suggestions;
    }

    /**
     * Get mapping statistics
     */
    public function getMappingStatistics(array $oldProgramNames): array
    {
        $validation = $this->validateProgramMappings($oldProgramNames);
        
        return [
            'total_programs' => count(array_unique($oldProgramNames)),
            'mapped_count' => count($validation['valid']),
            'default_count' => count($validation['using_default']),
            'unmapped_count' => count($validation['invalid']),
            'mapping_percentage' => count(array_unique($oldProgramNames)) > 0 
                ? round((count($validation['valid']) / count(array_unique($oldProgramNames))) * 100, 2)
                : 0,
        ];
    }

    /**
     * Clear mapping caches
     */
    public function clearMappingCaches(): void
    {
        $this->programCache = [];
        $this->funderMappingCache = [];
        Cache::forget('import_available_programs');
    }
}