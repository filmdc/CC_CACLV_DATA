<?php

namespace App\Livewire;

use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Services\Homes\HousingRecordDeleter;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin recycle bin for the two records the housing project manager can
 * delete from the portal: call/intake logs and projects.
 *
 * Deletes there are soft, so nothing here is a reconstruction — restoring a
 * row brings the record back exactly as it was, with its timeline,
 * requirements, milestones, budget, expenses and documents still attached.
 */
class RecycleBin extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    /** calls | projects */
    public string $tab = 'calls';

    public string $search = '';

    protected $queryString = [
        'tab' => ['except' => 'calls'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->authorizeAction('recycle_bin.manage');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['calls', 'projects'], true) ? $tab : 'calls';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function restoreCall(int $id, HousingRecordDeleter $deleter): void
    {
        $this->authorizeAction('recycle_bin.manage');

        $log = CommunicationLog::onlyTrashed()->findOrFail($id);
        $deleter->restoreCall($log);

        session()->flash('message', 'Restored the call for '.($log->contact_full_name ?: 'COMM-'.$log->id).'.');
    }

    public function restoreProject(int $id, HousingRecordDeleter $deleter): void
    {
        $this->authorizeAction('recycle_bin.manage');

        $job = HousingJob::onlyTrashed()->findOrFail($id);
        $deleter->restoreProject($job);

        session()->flash('message', "Restored project {$job->job_number}.");
    }

    public function render(HousingRecordDeleter $deleter)
    {
        $needle = trim($this->search);

        if ($this->tab === 'projects') {
            $rows = HousingJob::onlyTrashed()
                ->with(['participant', 'property', 'deletedBy'])
                ->when($needle !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('job_number', 'like', "%{$needle}%")
                    ->orWhere('job_title', 'like', "%{$needle}%")))
                ->orderByDesc('deleted_at')
                ->paginate(20)
                ->through(fn (HousingJob $job) => [
                    'id' => $job->id,
                    'title' => $job->job_number.' · '.$job->job_title,
                    'who' => $job->participant?->full_name ?: '—',
                    'where' => $job->property?->address_line_1 ?: '—',
                    'deleted_at' => $job->deleted_at?->isoFormat('MMM D, YYYY [at] h:mm a'),
                    'deleted_by' => $job->deletedBy?->name ?: 'Unknown',
                    'note' => null,
                ]);
        } else {
            $rows = CommunicationLog::onlyTrashed()
                ->with(['participant', 'deletedBy'])
                ->when($needle !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('contact_first_name', 'like', "%{$needle}%")
                    ->orWhere('contact_last_name', 'like', "%{$needle}%")
                    ->orWhere('subject', 'like', "%{$needle}%")))
                ->orderByDesc('deleted_at')
                ->paginate(20)
                ->through(fn (CommunicationLog $log) => [
                    'id' => $log->id,
                    'title' => 'COMM-'.$log->id.' · '.ucfirst(str_replace('_', ' ', (string) $log->communication_type)),
                    'who' => $log->participant?->full_name ?: ($log->contact_full_name ?: ($log->subject ?: '—')),
                    'where' => $log->contact_full_address ?: '—',
                    'deleted_at' => $log->deleted_at?->isoFormat('MMM D, YYYY [at] h:mm a'),
                    'deleted_by' => $log->deletedBy?->name ?: 'Unknown',
                    'note' => $deleter->restoreCallNote($log),
                ]);
        }

        return view('livewire.recycle-bin', [
            'rows' => $rows,
            'callCount' => CommunicationLog::onlyTrashed()->count(),
            'projectCount' => HousingJob::onlyTrashed()->count(),
        ]);
    }
}
