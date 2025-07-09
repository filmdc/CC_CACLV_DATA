<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanHtmlFromImportedData extends Command
{
    protected $signature = 'contract:clean-html {--dry-run : Show what would be cleaned without making changes}';
    protected $description = 'Clean HTML elements from imported contract notes and due date descriptions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting HTML cleanup process...');

        // Clean contract notes
        $contractsUpdated = $this->cleanContractNotes($dryRun);
        
        // Clean contract purpose fields
        $contractsPurposeUpdated = $this->cleanContractPurpose($dryRun);
        
        // Clean due date descriptions
        $dueDatesUpdated = $this->cleanDueDateDescriptions($dryRun);

        $this->newLine();
        $this->info('HTML cleanup completed!');
        $this->line("Contracts notes updated: {$contractsUpdated}");
        $this->line("Contracts purpose updated: {$contractsPurposeUpdated}");
        $this->line("Due date descriptions updated: {$dueDatesUpdated}");

        return 0;
    }

    private function cleanContractNotes(bool $dryRun): int
    {
        $this->info('Cleaning HTML from contract notes...');
        
        $contracts = Contract::whereNotNull('notes')
            ->where('notes', 'like', '%<%')
            ->get(['id', 'title', 'notes']);

        $updated = 0;

        foreach ($contracts as $contract) {
            $originalNotes = $contract->notes;
            $cleanedNotes = $this->cleanHtml($originalNotes);
            
            if ($originalNotes !== $cleanedNotes) {
                if ($dryRun) {
                    $this->line("Would clean contract: {$contract->title}");
                    $this->line("  Original: " . substr($originalNotes, 0, 100) . "...");
                    $this->line("  Cleaned:  " . substr($cleanedNotes, 0, 100) . "...");
                } else {
                    $contract->notes = $cleanedNotes;
                    $contract->save();
                    $this->line("Cleaned contract: {$contract->title}");
                }
                $updated++;
            }
        }

        return $updated;
    }

    private function cleanContractPurpose(bool $dryRun): int
    {
        $this->info('Cleaning HTML from contract purpose fields...');
        
        $contracts = Contract::whereNotNull('purpose')
            ->where('purpose', 'like', '%<%')
            ->get(['id', 'title', 'purpose']);

        $updated = 0;

        foreach ($contracts as $contract) {
            $originalPurpose = $contract->purpose;
            $cleanedPurpose = $this->cleanHtml($originalPurpose);
            
            if ($originalPurpose !== $cleanedPurpose) {
                if ($dryRun) {
                    $this->line("Would clean contract purpose: {$contract->title}");
                    $this->line("  Original: " . substr($originalPurpose, 0, 100) . "...");
                    $this->line("  Cleaned:  " . substr($cleanedPurpose, 0, 100) . "...");
                } else {
                    $contract->purpose = $cleanedPurpose;
                    $contract->save();
                    $this->line("Cleaned contract purpose: {$contract->title}");
                }
                $updated++;
            }
        }

        return $updated;
    }

    private function cleanDueDateDescriptions(bool $dryRun): int
    {
        $this->info('Cleaning HTML from due date descriptions...');
        
        $dueDates = ContractReportDueDate::whereNotNull('description')
            ->where('description', 'like', '%<%')
            ->get(['id', 'description']);

        $updated = 0;

        foreach ($dueDates as $dueDate) {
            $originalDescription = $dueDate->description;
            $cleanedDescription = $this->cleanHtml($originalDescription);
            
            if ($originalDescription !== $cleanedDescription) {
                if ($dryRun) {
                    $this->line("Would clean due date ID: {$dueDate->id}");
                    $this->line("  Original: " . substr($originalDescription, 0, 100) . "...");
                    $this->line("  Cleaned:  " . substr($cleanedDescription, 0, 100) . "...");
                } else {
                    $dueDate->description = $cleanedDescription;
                    $dueDate->save();
                    $this->line("Cleaned due date ID: {$dueDate->id}");
                }
                $updated++;
            }
        }

        return $updated;
    }

    private function cleanHtml(string $text): string
    {
        // First, decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Strip HTML tags but preserve the content
        $text = strip_tags($text);
        
        // Clean up multiple spaces and line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        // Convert multiple line breaks to double line breaks for readability
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return $text;
    }
}