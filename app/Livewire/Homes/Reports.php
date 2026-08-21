<?php

namespace App\Livewire\Homes;

use App\Services\Homes\HousingPortalReports;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Reports (homes.caclv.org): four canned reports — pick one, check the
 * numbers, export CSV. No query builder.
 */
#[Layout('components.layouts.homes')]
class Reports extends Component
{
    use WithPermissionChecks;

    #[Url]
    public string $report = 'activity';

    public const REPORTS = [
        'activity' => ['label' => 'Monthly activity', 'sub' => 'Calls, intakes, conversions — the board one', 'audience' => 'Board'],
        'funds' => ['label' => 'Fund spending', 'sub' => 'Allocated + spent per fund code, from project budgets', 'audience' => 'Funders'],
        'funnel' => ['label' => 'Intake funnel', 'sub' => 'Call → intake → docs → project', 'audience' => 'Board'],
        'status' => ['label' => 'Project status', 'sub' => 'Every open job, one line each', 'audience' => 'Funders'],
    ];

    public function mount(): void
    {
        $this->authorizeAction('housing.reports.view');

        if (! array_key_exists($this->report, self::REPORTS)) {
            $this->report = 'activity';
        }
    }

    public function setReport(string $report): void
    {
        $this->report = array_key_exists($report, self::REPORTS) ? $report : 'activity';
    }

    /** CSV export — same native stream idiom as ProjectReporting::exportReport(). */
    public function exportCsv(HousingPortalReports $reports)
    {
        $this->authorizeAction('housing.reports.view');

        $rows = $reports->csvRows($this->report);
        $filename = 'housing-'.$this->report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render(HousingPortalReports $reports)
    {
        $data = match ($this->report) {
            'funds' => $reports->fundSpending(),
            'funnel' => $reports->intakeFunnel(),
            'status' => ['rows' => $reports->projectStatus()],
            default => $reports->monthlyActivity(),
        };

        return view('livewire.homes.reports', [
            'reports' => self::REPORTS,
            'current' => self::REPORTS[$this->report],
            'data' => $data,
        ]);
    }
}
