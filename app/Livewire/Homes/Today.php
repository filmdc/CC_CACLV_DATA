<?php

namespace App\Livewire\Homes;

use App\Models\CommunicationLog;
use App\Services\Homes\HousingPortalData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.homes')]
class Today extends Component
{
    use WithPermissionChecks;

    /** Mark a follow-up done straight from the queue (reuses the model helper). */
    public function completeFollowUp(int $logId): void
    {
        $this->authorizeAction('communications_log.edit');

        $log = CommunicationLog::findOrFail($logId);
        $log->markFollowUpCompleted(Auth::id());

        session()->flash('homes_message', 'Follow-up marked complete.');
    }

    /** Refresh when a call is logged via the quick-log modal. */
    #[On('homes-call-logged')]
    public function refresh(): void
    {
        // no-op: re-render pulls fresh data
    }

    public function render(HousingPortalData $data)
    {
        $canViewIntakes = $this->can('communications_log.view') || $this->isAdmin();

        return view('livewire.homes.today', [
            'greetingName' => Auth::user()?->name ? explode(' ', Auth::user()->name)[0] : 'there',
            'today' => now()->isoFormat('dddd, MMM D'),
            'canViewIntakes' => $canViewIntakes,
            'followUps' => $canViewIntakes ? $data->followUpsDue() : collect(),
            'waiting' => $canViewIntakes ? $data->waitingOnDocuments() : collect(),
            'ready' => $canViewIntakes ? $data->readyToConvert() : collect(),
            'glance' => $data->weekGlance(),
        ]);
    }
}
