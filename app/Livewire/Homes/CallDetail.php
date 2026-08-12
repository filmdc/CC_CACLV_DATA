<?php

namespace App\Livewire\Homes;

use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Services\Homes\HousingPortalData;
use App\Services\IntakeConversion\ConversionBlocked;
use App\Services\IntakeConversion\ConversionInput;
use App\Services\IntakeConversion\ConvertIntakeToProject;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * One call/intake opened (homes.caclv.org): "Every touch" timeline,
 * document checklist with Received / Nudge, follow-up card, and the
 * convert-to-project modal running the SAME shared service as the main app.
 */
#[Layout('components.layouts.homes')]
class CallDetail extends Component
{
    use WithPermissionChecks;
    use WithProgramAccess;

    public CommunicationLog $log;

    // ── Convert modal state ───────────────────────────────────────
    public bool $showConvert = false;

    public int $convertStep = 1;

    public string $convertTitle = '';

    public string $convertDescription = '';

    public bool $requirementOverride = false;

    public string $requirementOverrideReason = '';

    public array $participantMatches = [];

    public array $propertyMatches = [];

    public string|int $participantAction = 'create_new';

    public string|int $propertyAction = 'create_new';

    // ── Follow-up editing ─────────────────────────────────────────
    public bool $editingFollowUp = false;

    public string $followUpAction = '';

    public string $followUpDate = '';

    public function mount(CommunicationLog $log): void
    {
        $this->authorizeAction('communications_log.view');
        $this->log = $log;
    }

    #[On('homes-call-logged')]
    public function refresh(): void
    {
        $this->log->refresh();
    }

    // ── Requirement actions (mirror the main app's gating exactly) ──

    public function markReceived(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        $req->markReceived(Auth::user());
        // A transition may flip the Phase-1 gate — same as the main app.
        $req->fresh()->communicationLog?->applyAutoCategory();

        $this->log->refresh();
        session()->flash('homes_message', 'Marked received.');
    }

    public function nudge(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        if ($req->status === IntakeRequirement::STATUS_PENDING) {
            // First ask: "we have asked the client" — writes its own
            // status_change timeline entry via applyTransition().
            $req->markRequested(Auth::user());
            $req->fresh()->communicationLog?->applyAutoCategory();
        } else {
            // Already asked — record the reminder on the timeline without
            // touching status.
            $label = $req->label ?? $req->source_label_snapshot ?? 'requirement';
            $this->log->addUpdate("Nudged homeowner re: {$label}", 'follow_up', Auth::id());
        }

        $this->log->refresh();
        session()->flash('homes_message', 'Nudge recorded.');
    }

    protected function ownRequirement(int $requirementId): IntakeRequirement
    {
        $this->authorizeAction('communications_log.edit');

        $req = IntakeRequirement::findOrFail($requirementId);

        if ($req->communication_log_id !== (int) $this->log->id) {
            abort(403);
        }

        return $req;
    }

    // ── Follow-up ─────────────────────────────────────────────────

    public function completeFollowUp(): void
    {
        $this->authorizeAction('communications_log.edit');

        $this->log->markFollowUpCompleted(Auth::id());
        $this->log->refresh();
        session()->flash('homes_message', 'Follow-up marked complete.');
    }

    public function startEditFollowUp(): void
    {
        $this->authorizeAction('communications_log.edit');

        $this->followUpAction = (string) ($this->log->follow_up_action ?? '');
        $this->followUpDate = $this->log->follow_up_date?->toDateString() ?? now()->addWeek()->toDateString();
        $this->editingFollowUp = true;
    }

    public function saveFollowUp(): void
    {
        $this->authorizeAction('communications_log.edit');

        $this->validate([
            'followUpAction' => 'required|string|max:255',
            'followUpDate' => 'required|date',
        ]);

        $this->log->update([
            'follow_up_action' => $this->followUpAction,
            'follow_up_date' => $this->followUpDate,
            'follow_up_completed' => false,
            'updated_by' => Auth::id(),
        ]);

        $this->editingFollowUp = false;
        $this->log->refresh();
        session()->flash('homes_message', 'Follow-up updated.');
    }

    // ── Convert to project (shared service, parity semantics) ────────

    public function openConvert(): void
    {
        $this->authorizeAction('projects.create');

        $this->convertTitle = (string) ($this->log->subject ?? '');
        $this->convertDescription = (string) ($this->log->summary ?? $this->log->notes ?? '');
        $this->convertStep = 1;
        $this->participantMatches = [];
        $this->propertyMatches = [];
        $this->participantAction = 'create_new';
        $this->propertyAction = 'create_new';
        $this->requirementOverride = false;
        $this->requirementOverrideReason = '';
        $this->showConvert = true;
    }

    public function closeConvert(): void
    {
        $this->showConvert = false;
        $this->convertStep = 1;
        $this->requirementOverride = false;
        $this->requirementOverrideReason = '';
    }

    /** Step 1 → duplicate review (when matches exist) or straight to convert. */
    public function continueConvert(ConvertIntakeToProject $service): void
    {
        $this->validate([
            'convertTitle' => 'required|string|max:255',
            'convertDescription' => 'nullable|string',
        ]);

        $duplicates = $service->findDuplicates($this->log);
        $this->participantMatches = $duplicates['participants'];
        $this->propertyMatches = $duplicates['properties'];

        if (! empty($this->participantMatches) || ! empty($this->propertyMatches)) {
            $this->convertStep = 2;

            return;
        }

        $this->doConvert($service);
    }

    public function doConvert(?ConvertIntakeToProject $service = null)
    {
        $this->authorizeAction('projects.create');
        $service ??= app(ConvertIntakeToProject::class);

        $this->validate([
            'convertTitle' => 'required|string|max:255',
            'convertDescription' => 'nullable|string',
        ]);

        try {
            $result = $service->convert(
                $this->log,
                new ConversionInput(
                    jobTitle: $this->convertTitle,
                    jobDescription: $this->convertDescription,
                    programId: $this->deriveProgramId(),
                    participantAction: $this->participantAction,
                    propertyAction: $this->propertyAction,
                    requirementOverride: $this->requirementOverride,
                    overrideReason: $this->requirementOverrideReason,
                ),
                Auth::user(),
            );
        } catch (ConversionBlocked $e) {
            match ($e->reasonCode) {
                ConversionBlocked::UNMET_REQUIREMENTS => $this->addError('requirementOverride', $e->getMessage()),
                ConversionBlocked::OVERRIDE_REASON_REQUIRED => $this->addError('requirementOverrideReason', $e->getMessage()),
                default => $this->addError('convertTitle', $e->getMessage()),
            };

            return;
        } catch (\Exception $e) {
            $this->addError('convertTitle', 'Failed to convert to project: '.$e->getMessage());

            return;
        }

        session()->flash('homes_message', "Project {$result->job->job_number} created — calls and documents stay linked here.");

        return redirect()->route('homes.project', $result->job);
    }

    /** Same silent auto-derive as the main app's openConvertModal. */
    protected function deriveProgramId(): ?int
    {
        $log = $this->log->load('services.program');

        foreach ($log->services as $service) {
            if ($service->program && $service->program->housing_module_enabled && $service->program->active) {
                return $service->program->id;
            }
        }

        return $this->getAccessiblePrograms()
            ->where('active', true)
            ->where('housing_module_enabled', true)
            ->first()?->id;
    }

    // ── Render ────────────────────────────────────────────────────

    public function render(HousingPortalData $data, ConvertIntakeToProject $convert)
    {
        $log = $this->log->load([
            'participant', 'property',
            'updates.createdBy',
            'intakeRequirements' => fn ($q) => $q->where('is_orphaned', false),
            'intakeRequirements.definition',
        ]);

        // "Every touch" — updates plus a synthesized root entry for the
        // original call, newest first.
        $timeline = $log->updates->map(fn ($u) => [
            'on' => $u->created_at,
            'kind' => $u->update_type_display,
            'color' => $u->update_type_color,
            'note' => (string) $u->update_content,
            'by' => $u->createdBy?->name,
        ]);

        $timeline->push([
            'on' => $log->communication_date,
            'kind' => ucfirst(str_replace('_', ' ', (string) $log->communication_type)).' · '.ucfirst((string) $log->direction),
            'color' => 'gray',
            'note' => (string) ($log->summary ?: $log->subject),
            'by' => null,
        ]);

        $timeline = $timeline->sortByDesc('on')->values();

        // Phase-1 checklist rows.
        $requirements = $log->intakeRequirements->map(fn (IntakeRequirement $r) => [
            'id' => $r->id,
            'label' => (string) ($r->label ?? $r->source_label_snapshot ?? 'Requirement'),
            'type' => $r->type,
            'status' => $r->status,
            'status_display' => IntakeRequirement::statusDisplay($r->status),
            'met' => $r->isMet(),
            'received_on' => $r->received_at?->isoFormat('MMM D'),
            'worker_note' => $r->worker_note,
        ])->values();

        $gate = $convert->gate($log);
        $progress = $data->phase1Progress($log);
        $stage = $data->stage($log);

        return view('livewire.homes.call-detail', [
            'who' => $data->personName($log),
            'where' => $data->personWhere($log),
            'stage' => $stage,
            'timeline' => $timeline,
            'requirements' => $requirements,
            'gate' => $gate,
            'progress' => $progress,
            'canConvert' => $gate['ready'] && ! $log->is_converted && $this->can('projects.create'),
            'canEdit' => $this->can('communications_log.edit'),
        ]);
    }
}
