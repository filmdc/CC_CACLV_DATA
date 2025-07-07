<?php

namespace App\Console\Commands;

use App\Models\ReferenceDataSet;
use App\Models\User;
use App\Services\CsbgReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateScheduledCsbgReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csbg:generate-scheduled-reports 
                            {--frequency=monthly : Report frequency (monthly, quarterly, yearly)}
                            {--format=all : Export format (csv, pdf, excel, all)}
                            {--email= : Email address to send reports to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scheduled CSBG reports and optionally email them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->option('frequency');
        $format = $this->option('format');
        $email = $this->option('email');
        
        $this->info("Generating scheduled CSBG reports ({$frequency})...");
        
        try {
            // Calculate date range based on frequency
            $dateRange = $this->calculateDateRange($frequency);
            
            // Get active reference data set
            $referenceDataSet = ReferenceDataSet::getActive();
            if (!$referenceDataSet) {
                $this->error('No active reference data set found.');
                return Command::FAILURE;
            }
            
            // Generate report
            $reportService = new CsbgReportService();
            $filters = [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
                'reference_data_set_id' => $referenceDataSet->id,
                'heads_of_household_only' => true,
            ];
            
            $reportData = $reportService->generateReport($filters);
            
            $this->info("Report generated for period: {$dateRange['start']} to {$dateRange['end']}");
            $this->info("Total participants: " . number_format($reportData['participant_count']));
            
            // Generate exports
            $files = [];
            
            if ($format === 'all' || $format === 'csv') {
                $files['csv'] = $this->generateCsvExport($reportService, $reportData, $dateRange);
            }
            
            if ($format === 'all' || $format === 'pdf') {
                $files['pdf'] = $this->generatePdfExport($reportService, $reportData, $dateRange);
            }
            
            if ($format === 'all' || $format === 'excel') {
                $files['excel'] = $this->generateExcelExport($reportService, $reportData, $dateRange);
            }
            
            // Email reports if requested
            if ($email) {
                $this->emailReports($email, $files, $dateRange, $frequency);
            }
            
            // Log the scheduled report generation
            Log::info('Scheduled CSBG report generated', [
                'frequency' => $frequency,
                'format' => $format,
                'period_start' => $dateRange['start'],
                'period_end' => $dateRange['end'],
                'participant_count' => $reportData['participant_count'],
                'files_generated' => array_keys($files),
                'emailed_to' => $email
            ]);
            
            $this->info('Scheduled CSBG reports generated successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error generating scheduled reports: ' . $e->getMessage());
            Log::error('Scheduled CSBG report generation failed', [
                'error' => $e->getMessage(),
                'frequency' => $frequency,
                'format' => $format
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Calculate date range based on frequency
     */
    private function calculateDateRange(string $frequency): array
    {
        $now = Carbon::now();
        
        switch ($frequency) {
            case 'monthly':
                $start = $now->copy()->startOfMonth()->subMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
                
            case 'quarterly':
                $start = $now->copy()->startOfQuarter()->subQuarter();
                $end = $now->copy()->subQuarter()->endOfQuarter();
                break;
                
            case 'yearly':
                // Use fiscal year (October 1 - September 30)
                $currentYear = $now->year;
                $fiscalYearStart = $now->month >= 10 ? $currentYear : $currentYear - 1;
                $start = Carbon::create($fiscalYearStart - 1, 10, 1);
                $end = Carbon::create($fiscalYearStart, 9, 30);
                break;
                
            default:
                throw new \InvalidArgumentException("Invalid frequency: {$frequency}");
        }
        
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }
    
    /**
     * Generate CSV export
     */
    private function generateCsvExport(CsbgReportService $service, array $reportData, array $dateRange): string
    {
        $filename = "csbg_scheduled_report_{$dateRange['start']}_to_{$dateRange['end']}.csv";
        $content = $service->exportToCsv($reportData);
        
        Storage::disk('local')->put("reports/scheduled/{$filename}", $content);
        
        $this->info("CSV report saved: {$filename}");
        
        return $filename;
    }
    
    /**
     * Generate PDF export
     */
    private function generatePdfExport(CsbgReportService $service, array $reportData, array $dateRange): string
    {
        $filename = "csbg_scheduled_report_{$dateRange['start']}_to_{$dateRange['end']}.pdf";
        $content = $service->exportToPdf($reportData);
        
        Storage::disk('local')->put("reports/scheduled/{$filename}", $content);
        
        $this->info("PDF report saved: {$filename}");
        
        return $filename;
    }
    
    /**
     * Generate Excel export
     */
    private function generateExcelExport(CsbgReportService $service, array $reportData, array $dateRange): string
    {
        $filename = "csbg_scheduled_report_{$dateRange['start']}_to_{$dateRange['end']}.xlsx";
        $content = $service->exportToExcel($reportData);
        
        Storage::disk('local')->put("reports/scheduled/{$filename}", $content);
        
        $this->info("Excel report saved: {$filename}");
        
        return $filename;
    }
    
    /**
     * Email reports to specified address
     */
    private function emailReports(string $email, array $files, array $dateRange, string $frequency): void
    {
        try {
            // In a real implementation, you'd create a proper Mailable class
            // For now, we'll just log that the email would be sent
            $this->info("Would email reports to: {$email}");
            $this->info("Files to attach: " . implode(', ', $files));
            
            // TODO: Implement actual email sending with Laravel's Mail facade
            // Mail::to($email)->send(new ScheduledCsbgReport($files, $dateRange, $frequency));
            
        } catch (\Exception $e) {
            $this->warn("Failed to email reports: " . $e->getMessage());
        }
    }
}
