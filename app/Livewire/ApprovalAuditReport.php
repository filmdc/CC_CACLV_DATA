<?php

namespace App\Livewire;

use App\Models\ApprovalHistory;
use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ApprovalAuditReport extends Component
{
    use WithPagination, WithPermissionChecks;

    public $dateFrom = '';

    public $dateTo = '';

    public $filterStatus = 'all';

    public $filterType = '';

    public $filterProgram = '';

    public $filterApprover = '';

    public $search = '';

    public function mount()
    {
        $this->authorizeAction('approvals.view_dashboard');
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function updatedFilterApprover()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    /**
     * Build the base query with all filters applied (reusable for both display and export).
     */
    protected function buildFilteredQuery()
    {
        $query = ApprovalHistory::with(['approvable', 'user', 'context', 'approvalType']);

        // Date range filter
        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        // Status filter
        if ($this->filterStatus && $this->filterStatus !== 'all') {
            $query->where('action', $this->filterStatus);
        }

        // Approval type filter
        if ($this->filterType) {
            $query->where('approval_type_id', $this->filterType);
        }

        // Program filter - join through PendingApproval which has program_id
        if ($this->filterProgram) {
            $programId = $this->filterProgram;
            $query->where(function ($q) use ($programId) {
                $q->whereExists(function ($sub) use ($programId) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('pending_approvals')
                        ->whereColumn('pending_approvals.approvable_type', 'approval_history.approvable_type')
                        ->whereColumn('pending_approvals.approvable_id', 'approval_history.approvable_id')
                        ->where('pending_approvals.program_id', $programId);
                });
            });
        }

        // Approver filter
        if ($this->filterApprover) {
            $query->where('user_id', $this->filterApprover);
        }

        // Search filter
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })
                    ->orWhere('reason', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhere('approvable_type', 'like', '%'.$search.'%');
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getHistoryProperty()
    {
        return $this->buildFilteredQuery()->paginate(25);
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

        return [
            'approved' => (clone $baseQuery)->where('action', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('action', 'rejected')->count(),
            'resubmitted' => (clone $baseQuery)->where('action', 'resubmitted')->count(),
        ];
    }

    public function resetFilters()
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->filterStatus = 'all';
        $this->filterType = '';
        $this->filterProgram = '';
        $this->filterApprover = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Resolve a human-readable label for the approvable type.
     */
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

    /**
     * Resolve a human-readable name for the approvable item.
     */
    protected function resolveApprovableName($history): string
    {
        $approvable = $history->approvable;

        if (! $approvable) {
            return 'Deleted Item #'.$history->approvable_id;
        }

        // Try common name attributes
        return $approvable->title
            ?? $approvable->name
            ?? $approvable->item_name
            ?? $approvable->file_name
            ?? (class_basename($approvable).' #'.$approvable->id);
    }

    /**
     * Resolve the category from the approvable or approval type.
     */
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

    /**
     * Resolve the program name from the related PendingApproval.
     */
    protected function resolveProgramName($history): string
    {
        // Try to find program via PendingApproval
        $pending = PendingApproval::where('approvable_type', $history->approvable_type)
            ->where('approvable_id', $history->approvable_id)
            ->whereNotNull('program_id')
            ->with('program')
            ->first();

        if ($pending && $pending->program) {
            return $pending->program->name;
        }

        // Try the approvable itself if it has a program relationship
        if ($history->approvable && method_exists($history->approvable, 'program') && $history->approvable->program) {
            return $history->approvable->program->name;
        }

        return '-';
    }

    public function exportToExcel()
    {
        $this->authorizeAction('approvals.view_dashboard');

        try {
            $records = $this->buildFilteredQuery()->get();

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Approval Audit Report');

            // Header style
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            // Title row
            $dateRange = Carbon::parse($this->dateFrom)->format('M j, Y').' - '.Carbon::parse($this->dateTo)->format('M j, Y');
            $sheet->setCellValue('A1', 'Approval Audit Report: '.$dateRange);
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            // Summary row
            $approvedCount = $records->where('action', 'approved')->count();
            $rejectedCount = $records->where('action', 'rejected')->count();
            $resubmittedCount = $records->where('action', 'resubmitted')->count();
            $sheet->setCellValue('A2', "Total Records: {$records->count()} | Approved: {$approvedCount} | Rejected: {$rejectedCount} | Resubmitted: {$resubmittedCount}");
            $sheet->mergeCells('A2:J2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 11, 'color' => ['rgb' => '6B7280']],
            ]);

            // Column headers (row 4)
            $headers = [
                'A' => 'Date',
                'B' => 'Action',
                'C' => 'Item',
                'D' => 'Type',
                'E' => 'Category',
                'F' => 'Actioned By',
                'G' => 'Chain Step',
                'H' => 'IP Address',
                'I' => 'Program',
                'J' => 'Reason/Notes',
            ];

            $headerRow = 4;
            foreach ($headers as $col => $label) {
                $sheet->setCellValue($col.$headerRow, $label);
            }
            $sheet->getStyle('A'.$headerRow.':J'.$headerRow)->applyFromArray($headerStyle);

            // Data rows
            $row = $headerRow + 1;
            foreach ($records as $record) {
                $sheet->setCellValue('A'.$row, $record->created_at->format('Y-m-d H:i:s'));
                $sheet->setCellValue('B'.$row, $record->action_label);
                $sheet->setCellValue('C'.$row, $this->resolveApprovableName($record));
                $sheet->setCellValue('D'.$row, $this->resolveApprovableLabel($record));
                $sheet->setCellValue('E'.$row, $this->resolveCategory($record));
                $sheet->setCellValue('F'.$row, $record->user?->name ?? 'System');
                $sheet->setCellValue('G'.$row, $record->chain_step ?? '-');
                $sheet->setCellValue('H'.$row, $record->signature_ip ?? '-');
                $sheet->setCellValue('I'.$row, $this->resolveProgramName($record));
                $sheet->setCellValue('J'.$row, $record->reason ?? $record->notes ?? '-');

                // Alternate row shading
                if ($row % 2 === 0) {
                    $sheet->getStyle('A'.$row.':J'.$row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                }

                $row++;
            }

            // Data borders
            $lastDataRow = $row - 1;
            if ($lastDataRow >= $headerRow + 1) {
                $sheet->getStyle('A'.($headerRow + 1).':J'.$lastDataRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
            }

            // Auto-size columns
            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Save file
            $filename = 'approval_audit_report_'.Carbon::now()->format('Y-m-d_His').'.xlsx';
            $filepath = storage_path('app/exports/'.$filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            session()->put('audit_report_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route('approvals.audit-report-download', ['file' => $filename]);

        } catch (\Exception $e) {
            Log::error('ApprovalAuditReport: Failed to generate Excel export', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to generate Excel export: '.$e->getMessage());
        }
    }

    public function render()
    {
        // Get approval types for the filter dropdown
        $approvalTypes = ApprovalType::orderBy('display_name')->get();

        // Get programs for the filter dropdown
        $programs = Program::where('active', true)->orderBy('name')->get();

        // Get users who have actioned approvals for the filter dropdown
        $approverIds = ApprovalHistory::distinct()->pluck('user_id')->filter();
        $users = User::whereIn('id', $approverIds)->orderBy('name')->get();

        return view('livewire.approval-audit-report', [
            'history' => $this->history,
            'stats' => $this->stats,
            'approvalTypes' => $approvalTypes,
            'programs' => $programs,
            'users' => $users,
        ])->layout('components.layouts.app');
    }
}
