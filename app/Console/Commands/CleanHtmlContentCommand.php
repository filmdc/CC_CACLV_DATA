<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Program;
use App\Models\Service;
use App\Models\WorkPlan;
use App\Models\WorkPlanGoal;
use Illuminate\Console\Command;

class CleanHtmlContentCommand extends Command
{
    protected $signature = 'content:clean-html {--dry-run : Preview changes without applying them} {--model= : Clean specific model only}';
    
    protected $description = 'Clean HTML content from text fields throughout the application';

    private $modelsToClean = [
        'Contract' => ['purpose', 'notes'],
        'Program' => ['description', 'notes'],
        'Service' => ['description', 'notes'],
        'WorkPlan' => ['description'],
        'WorkPlanGoal' => ['description'],
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificModel = $this->option('model');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Starting HTML content cleanup...');
        
        $totalCleaned = 0;
        $totalProcessed = 0;

        foreach ($this->modelsToClean as $modelName => $fields) {
            if ($specificModel && $specificModel !== $modelName) {
                continue;
            }

            $modelClass = "App\\Models\\$modelName";
            $this->info("\nProcessing $modelName model...");

            foreach ($fields as $field) {
                $this->line("  Cleaning field: $field");
                
                $recordsWithHtml = $modelClass::where($field, 'like', '%<%')
                    ->where($field, 'like', '%>%')
                    ->whereNotNull($field)
                    ->get();

                $cleanedInField = 0;
                foreach ($recordsWithHtml as $record) {
                    $originalContent = $record->$field;
                    $cleanedContent = $this->cleanHtmlContent($originalContent);
                    
                    if ($originalContent !== $cleanedContent) {
                        if (!$dryRun) {
                            $record->$field = $cleanedContent;
                            $record->save();
                        }
                        
                        $cleanedInField++;
                        
                        if ($this->output->isVerbose()) {
                            $this->line("    Record {$record->id}:");
                            $this->line("      Before: " . substr(str_replace(["\n", "\r"], ' ', $originalContent), 0, 100) . "...");
                            $this->line("      After:  " . substr(str_replace(["\n", "\r"], ' ', $cleanedContent), 0, 100) . "...");
                        }
                    }
                }

                $action = $dryRun ? 'Would clean' : 'Cleaned';
                $this->info("    $action $cleanedInField records in $field field");
                $totalCleaned += $cleanedInField;
                $totalProcessed += $recordsWithHtml->count();
            }
        }

        $this->newLine();
        $this->info("Cleanup completed!");
        
        $action = $dryRun ? 'Would clean' : 'Cleaned';
        $this->table(['Summary', 'Count'], [
            ['Total records processed', $totalProcessed],
            ['Records with HTML content', $totalCleaned],
        ]);

        if ($dryRun) {
            $this->info('This was a DRY RUN - no actual changes were made');
            $this->info('Run without --dry-run to apply changes');
            $this->info('Use -v flag for verbose output to see before/after content');
        }

        return Command::SUCCESS;
    }

    private function cleanHtmlContent($content)
    {
        if (empty($content)) {
            return $content;
        }

        // Convert common HTML entities back to characters
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Replace paragraph tags with double line breaks
        $content = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $content);
        $content = preg_replace('/<p[^>]*>/i', '', $content);
        $content = str_replace('</p>', "\n\n", $content);
        
        // Replace break tags with line breaks
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content);
        
        // Replace list items with bullet points
        $content = preg_replace('/<li[^>]*>/i', '• ', $content);
        $content = str_replace('</li>', "\n", $content);
        
        // Remove list containers
        $content = preg_replace('/<\/?[uo]l[^>]*>/i', '', $content);
        
        // Replace headers with bold formatting (we'll keep some structure)
        $content = preg_replace('/<h[1-6][^>]*>/i', '**', $content);
        $content = preg_replace('/<\/h[1-6]>/i', "**\n\n", $content);
        
        // Replace bold and italic tags with markdown-style formatting
        $content = preg_replace('/<(strong|b)[^>]*>/i', '**', $content);
        $content = preg_replace('/<\/(strong|b)>/i', '**', $content);
        $content = preg_replace('/<(em|i)[^>]*>/i', '*', $content);
        $content = preg_replace('/<\/(em|i)>/i', '*', $content);
        
        // Remove all remaining HTML tags
        $content = strip_tags($content);
        
        // Clean up excessive whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        $content = preg_replace('/[ \t]+/', ' ', $content);
        
        // Trim and return
        return trim($content);
    }
}