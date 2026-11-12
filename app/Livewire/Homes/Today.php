<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Concerns\HasPins;
use App\Models\CommunicationLog;
use App\Services\Homes\DashboardWidgets;
use App\Services\Homes\HousingPortalData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.homes')]
class Today extends Component
{
    use HasPins;
    use WithPermissionChecks;

    /**
     * Which fund group on the "Projects by fund" card is expanded. One at a
     * time — the card is a dashboard tile, not a second Projects list.
     */
    public ?int $openFund = null;

    public function toggleFund(int $fundCodeId): void
    {
        $this->openFund = $this->openFund === $fundCodeId ? null : $fundCodeId;
    }

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

    public function render(HousingPortalData $data, DashboardWidgets $widgets)
    {
        $canViewIntakes = $this->can('communications_log.view') || $this->isAdmin();
        $canViewProjects = $this->can('projects.view') || $this->isAdmin();
        $userId = (int) Auth::id();

        // A hidden card is not queried at all — the toggle saves the work,
        // not just the markup.
        $show = $widgets->state();

        return view('livewire.homes.today', [
            'greetingName' => Auth::user()?->name ? explode(' ', Auth::user()->name)[0] : 'there',
            'today' => now()->isoFormat('dddd, MMM D'),
            'canViewIntakes' => $canViewIntakes,
            'canViewProjects' => $canViewProjects,
            'show' => $show,
            'pinnedCalls' => $show['pinned'] && $canViewIntakes ? $data->pinnedCalls($userId) : collect(),
            'pinnedProjects' => $show['pinned'] && $canViewProjects ? $data->pinnedProjects($userId) : collect(),
            'followUps' => $show['follow_ups'] && $canViewIntakes ? $data->followUpsDue() : collect(),
            'waiting' => $show['waiting'] && $canViewIntakes ? $data->waitingOnDocuments() : collect(),
            'ready' => $show['ready'] && $canViewIntakes ? $data->readyToConvert() : collect(),
            'fundGroups' => $show['funds'] && $canViewProjects ? $data->projectsByFund() : collect(),
            'fundsWithoutProjects' => $show['funds'] && $canViewProjects ? $data->fundsWithoutProjects() : collect(),
            'glance' => $show['glance'] ? $data->weekGlance() : null,
            // The greeting line normally counts follow-ups; with that card
            // hidden it would point at something she cannot see.
            'openFollowUps' => $canViewIntakes ? $data->followUpsDueCount() : 0,
        ]);
    }
}
