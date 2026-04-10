<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalHistory;
use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TimelineReport extends Component
{
    use WithPagination, WithPermissionChecks;

    public $datePreset = 'this_month';
    public $dateFrom = '';
    public $dateTo = '';
    public $filterStatus = 'all';
    public $filterType = '';
    public $filterProgram = '';
    public $filterApprover = '';
    public $search = '';
    public $detailLevel = 'standard';

    public function mount()
    {
        $this->authorizeAction('approvals.view_audit_log');
        $this->applyDatePreset('this_month');
    }

    public function applyDatePreset($preset)
    {
        $this->datePreset = $preset;
        $now = Carbon::now();

        switch ($preset) {
            case 'this_week':
                $this->dateFrom = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'this_month':
                $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->dateFrom = $now->copy()->firstOfQuarter()->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'custom':
                break;
        }

        $this->resetPage();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedFilterType() { $this->resetPage(); }
    public function updatedFilterProgram() { $this->resetPage(); }
    public function updatedFilterApprover() { $this->resetPage(); }
    public function updatedDateFrom() { $this->datePreset = 'custom'; $this->resetPage(); }
    public function updatedDateTo() { $this->datePreset = 'custom'; $this->resetPage(); }

    protected function buildFilteredQuery()
    {
        $query = ApprovalHistory::with(['approvable', 'user', 'context', 'approvalType']);

        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }
        if ($this->filterStatus && $this->filterStatus !== 'all') {
            $query->where('action', $this->filterStatus);
        }
        if ($this->filterType) {
            $query->where('approval_type_id', $this->filterType);
        }
        if ($this->filterProgram) {
            $programId = $this->filterProgram;
            $query->where(function ($q) use ($programId) {
                $q->whereExists(function ($sub) use ($programId) {
                    $sub->select(DB::raw(1))
                        ->from('pending_approvals')
                        ->whereColumn('pending_approvals.approvable_type', 'approval_history.approvable_type')
                        ->whereColumn('pending_approvals.approvable_id', 'approval_history.approvable_id')
                        ->where('pending_approvals.program_id', $programId);
                });
            });
        }
        if ($this->filterApprover) {
            $query->where('user_id', $this->filterApprover);
        }
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })
                ->orWhere('reason', 'like', '%' . $search . '%')
                ->orWhere('notes', 'like', '%' . $search . '%')
                ->orWhere('approvable_type', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getStatsProperty()
    {
        $baseQuery = ApprovalHistory::query();

        if ($this->dateFrom) {
            $baseQuery->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if ($this->dateTo) {
            $baseQuery->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        $total = (clone $baseQuery)->count();
        $approved = (clone $baseQuery)->where('action', 'approved')->count();
        $rejected = (clone $baseQuery)->where('action', 'rejected')->count();
        $resubmitted = (clone $baseQuery)->where('action', 'resubmitted')->count();

        $byType = (clone $baseQuery)
            ->select('approval_type_id', DB::raw('count(*) as count'))
            ->groupBy('approval_type_id')
            ->with('approvalType')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->approvalType?->display_name ?? 'Unknown',
                'count' => $row->count,
            ]);

        $byProgram = [];
        $historyIds = (clone $baseQuery)->pluck('id');
        if ($historyIds->isNotEmpty()) {
            $byProgram = DB::table('approval_history')
                ->join('pending_approvals', function ($join) {
                    $join->on('pending_approvals.approvable_type', '=', 'approval_history.approvable_type')
                         ->on('pending_approvals.approvable_id', '=', 'approval_history.approvable_id');
                })
                ->join('programs', 'programs.id', '=', 'pending_approvals.program_id')
                ->whereIn('approval_history.id', $historyIds)
                ->select('programs.name', DB::raw('count(distinct approval_history.id) as count'))
                ->groupBy('programs.name')
                ->orderByDesc('count')
                ->get();
        }

        $byApprover = (clone $baseQuery)
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->user?->name ?? 'System',
                'count' => $row->count,
            ])
            ->sortByDesc('count')
            ->values();

        return [
            'total' => $total,
            'approved' => $approved,
            'rejected' => $rejected,
            'resubmitted' => $resubmitted,
            'byType' => $byType,
            'byProgram' => $byProgram,
            'byApprover' => $byApprover,
        ];
    }

    public function getTimelineProperty()
    {
        return $this->buildFilteredQuery()->paginate(25);
    }

    protected function resolveApprovableName($history): string
    {
        $approvable = $history->approvable;
        if (! $approvable) {
            return 'Deleted Item #' . $history->approvable_id;
        }
        return $approvable->title
            ?? $approvable->name
            ?? $approvable->item_name
            ?? $approvable->file_name
            ?? (class_basename($approvable) . ' #' . $approvable->id);
    }

    protected function resolveApprovableLabel($history): string
    {
        if ($history->approvable && method_exists($history->approvable, 'getApprovalTypeLabel')) {
            return $history->approvable->getApprovalTypeLabel();
        }
        if ($history->approvable_type) {
            return class_basename($history->approvable_type);
        }
        return 'Unknown';
    }

    protected function resolveCategory($history): string
    {
        if ($history->approvalType) {
            return $history->approvalType->category ?? $history->approvalType->display_name ?? 'General';
        }
        if ($history->approvable && method_exists($history->approvable, 'getApprovalCategory')) {
            return $history->approvable->getApprovalCategory();
        }
        return 'General';
    }

    protected function resolveProgramName($history): string
    {
        $pending = PendingApproval::where('approvable_type', $history->approvable_type)
            ->where('approvable_id', $history->approvable_id)
            ->whereNotNull('program_id')
            ->with('program')
            ->first();

        if ($pending && $pending->program) {
            return $pending->program->name;
        }

        if ($history->approvable && method_exists($history->approvable, 'program') && $history->approvable->program) {
            return $history->approvable->program->name;
        }

        return '-';
    }

    protected function buildExportRows($records)
    {
        $isDetailed = $this->detailLevel === 'detailed';

        return $records->map(function ($record) use ($isDetailed) {
            $row = [
                'Date' => $record->created_at->format('Y-m-d H:i:s'),
                'Action' => $record->action_label,
                'Item' => $this->resolveApprovableName($record),
                'Type' => $this->resolveApprovableLabel($record),
                'Actioned By' => $record->user?->name ?? 'System',
                'Program' => $this->resolveProgramName($record),
            ];

            if ($isDetailed) {
                $row['Category'] = $this->resolveCategory($record);
                $row['Chain Step'] = $record->chain_step ?? '-';
                $row['Reason/Notes'] = $record->reason ?? $record->notes ?? '-';
            }

            return $row;
        });
    }

    public function exportToPdf()
    {
        $this->authorizeAction('approvals.view_audit_log');

        try {
            $records = $this->buildFilteredQuery()->get();
            $stats = $this->stats;
            $rows = $this->detailLevel !== 'summary' ? $this->buildExportRows($records) : collect();

            $pdf = Pdf::loadView('exports.timeline-report-pdf', [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'detailLevel' => $this->detailLevel,
                'stats' => $stats,
                'rows' => $rows,
                'generatedAt' => Carbon::now()->format('M j, Y g:i A'),
            ]);

            $pdf->setPaper('A4', 'landscape');

            $filename = 'timeline_report_' . Carbon::now()->format('Y-m-d_His') . '.pdf';
            $filepath = storage_path('app/exports/' . $filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $pdf->save($filepath);

            session()->put('timeline_report_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route('mobile.report.download', ['file' => $filename]);
        } catch (\Exception $e) {
            Log::error('TimelineReport: PDF export failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function exportToExcel()
    {
        $this->authorizeAction('approvals.view_audit_log');

        try {
            $records = $this->buildFilteredQuery()->get();
            $stats = $this->stats;

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Timeline Report');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            $dateRange = Carbon::parse($this->dateFrom)->format('M j, Y') . ' - ' . Carbon::parse($this->dateTo)->format('M j, Y');
            $sheet->setCellValue('A1', 'Approval Timeline Report: ' . $dateRange);
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            ]);

            $sheet->setCellValue('A2', "Total: {$stats['total']} | Approved: {$stats['approved']} | Rejected: {$stats['rejected']} | Resubmitted: {$stats['resubmitted']}");
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 11, 'color' => ['rgb' => '6B7280']],
            ]);

            if ($this->detailLevel !== 'summary') {
                $rows = $this->buildExportRows($records);
                $headers = $rows->isNotEmpty() ? array_keys($rows->first()) : ['Date', 'Action', 'Item', 'Type', 'Actioned By', 'Program'];
                $lastCol = chr(64 + count($headers));

                $headerRow = 4;
                foreach ($headers as $i => $label) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue($col . $headerRow, $label);
                }
                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray($headerStyle);

                $row = $headerRow + 1;
                foreach ($rows as $data) {
                    $colIdx = 0;
                    foreach ($data as $value) {
                        $col = chr(65 + $colIdx);
                        $sheet->setCellValue($col . $row, $value);
                        $colIdx++;
                    }
                    if ($row % 2 === 0) {
                        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                        ]);
                    }
                    $row++;
                }

                foreach (range(0, count($headers) - 1) as $i) {
                    $sheet->getColumnDimension(chr(65 + $i))->setAutoSize(true);
                }
            }

            $filename = 'timeline_report_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
            $filepath = storage_path('app/exports/' . $filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            session()->put('timeline_report_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route('mobile.report.download', ['file' => $filename]);
        } catch (\Exception $e) {
            Log::error('TimelineReport: Excel export failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate Excel export: ' . $e->getMessage());
        }
    }

    public function exportToCsv()
    {
        $this->authorizeAction('approvals.view_audit_log');

        try {
            $records = $this->buildFilteredQuery()->get();
            $rows = $this->buildExportRows($records);

            $filename = 'timeline_report_' . Carbon::now()->format('Y-m-d_His') . '.csv';
            $filepath = storage_path('app/exports/' . $filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $csv = Writer::createFromPath($filepath, 'w+');
            $csv->setOutputBOM(Writer::BOM_UTF8);

            if ($rows->isNotEmpty()) {
                $csv->insertOne(array_keys($rows->first()));
                foreach ($rows as $row) {
                    $csv->insertOne(array_values($row));
                }
            }

            session()->put('timeline_report_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route('mobile.report.download', ['file' => $filename]);
        } catch (\Exception $e) {
            Log::error('TimelineReport: CSV export failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to generate CSV export: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->filterStatus = 'all';
        $this->filterType = '';
        $this->filterProgram = '';
        $this->filterApprover = '';
        $this->search = '';
        $this->detailLevel = 'standard';
        $this->applyDatePreset('this_month');
    }

    public function render()
    {
        $approvalTypes = ApprovalType::orderBy('display_name')->get();
        $programs = Program::where('active', true)->orderBy('name')->get();
        $approverIds = ApprovalHistory::distinct()->pluck('user_id')->filter();
        $users = User::whereIn('id', $approverIds)->orderBy('name')->get();

        return view('livewire.mobile.timeline-report', [
            'stats' => $this->stats,
            'timeline' => $this->detailLevel !== 'summary' ? $this->timeline : null,
            'approvalTypes' => $approvalTypes,
            'programs' => $programs,
            'users' => $users,
        ])->layout('components.layouts.approvals');
    }
}
