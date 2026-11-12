<?php

namespace App\Livewire\Homes;

use App\Livewire\Concerns\ManagesIncomeEligibility;
use App\Livewire\Homes\Concerns\HasPins;
use App\Models\CommunicationLog;
use App\Models\CommunicationLogUpdate;
use App\Models\HomesPin;
use App\Models\HousingService;
use App\Models\IntakeIncomeAssessment;
use App\Models\IntakeRequirement;
use App\Services\CommunicationLogMergeService;
use App\Services\Homes\HousingPortalData;
use App\Services\Homes\HousingRecordDeleter;
use App\Services\IntakeConversion\ConversionBlocked;
use App\Services\IntakeConversion\ConversionInput;
use App\Services\IntakeConversion\ConvertIntakeToProject;
use App\Services\IntakeRequirementInstantiator;
use App\Services\MediaValidationService;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * One call/intake opened (homes.caclv.org): "Every touch" timeline,
 * document checklist with Received / waive / undo, follow-up card, and the
 * convert-to-project modal running the SAME shared service as the main app.
 */
#[Layout('components.layouts.homes')]
class CallDetail extends Component
{
    use HasPins;
    use ManagesIncomeEligibility;
    use WithFileUploads;
    use WithPermissionChecks;
    use WithProgramAccess;

    public CommunicationLog $log;

    // ── Edit-log modal state ──────────────────────────────────────
    public bool $showEdit = false;

    public array $edit = [];

    // ── Ad-hoc requirement form ───────────────────────────────────
    public bool $showAddReq = false;

    public string $adHocLabel = '';

    public string $adHocType = IntakeRequirement::TYPE_DOCUMENT;

    // ── Per-requirement file upload ───────────────────────────────
    public ?int $uploadingRequirementId = null;

    public array $requirementFiles = [];

    // ── Per-requirement action modal ──────────────────────────────
    // Parity with the main app's requirement modal: waive / N/A / reopen
    // and the standing worker note, all of which the fast-path Received
    // button on the row deliberately leaves out.
    public ?int $actingOnRequirementId = null;

    public string $reqActionReason = '';

    public string $reqWorkerNote = '';

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

    // ── Merge-duplicate modal state ───────────────────────────────
    // Step 1 picks the duplicate, step 2 shows exactly what will move.
    public bool $showMerge = false;

    public int $mergeStep = 1;

    public string $mergeSearch = '';

    public ?int $mergeDuplicateId = null;

    // ── Timeline entry editing ────────────────────────────────────
    // One "Every touch" entry opened for correction, and the confirm step
    // for removing one. Removals are recoverable, so the removed entries
    // stay reachable from the card itself.
    public ?int $editingEntryId = null;

    public string $entryContent = '';

    public string $entryType = 'note';

    public string $entryDate = '';

    public ?int $removingEntryId = null;

    public bool $showRemovedEntries = false;

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

    // ── Delete (recoverable — admin can restore from the recycle bin) ──

    public bool $showDelete = false;

    public function confirmDelete(HousingRecordDeleter $deleter): void
    {
        $this->authorizeAction('communications_log.delete');

        $blocked = $deleter->callBlockedReason($this->log);
        if ($blocked !== null) {
            session()->flash('homes_error', $blocked);

            return;
        }

        $this->showDelete = true;
    }

    /** Explicit Cancel is the only close path (portal modal convention). */
    public function cancelDelete(): void
    {
        $this->showDelete = false;
    }

    public function deleteCall(HousingRecordDeleter $deleter)
    {
        $this->authorizeAction('communications_log.delete');

        $blocked = $deleter->callBlockedReason($this->log);
        if ($blocked !== null) {
            $this->showDelete = false;
            session()->flash('homes_error', $blocked);

            return null;
        }

        $who = $this->log->contact_full_name ?: ('COMM-'.$this->log->id);
        $deleter->deleteCall($this->log);

        session()->flash('homes_message', "Deleted the call for {$who}. If that was a mistake, an admin can restore it.");

        return redirect()->route('homes.calls');
    }

    // ── Pin (HasPins) ──────────────────────────────────────────────

    public function isCallPinned(): bool
    {
        return $this->isPinned(HomesPin::TYPE_CALL, $this->log->id);
    }

    public function toggleCallPin(): void
    {
        $this->togglePin(HomesPin::TYPE_CALL, $this->log->id);
    }

    // ── Income eligibility (shared trait hooks) ───────────────────

    protected function incomeEligibilityLog(): ?CommunicationLog
    {
        return $this->log;
    }

    protected function incomeFlashKey(): string
    {
        return 'homes_message';
    }

    protected function afterIncomeEligibilitySaved(): void
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

    /**
     * DORMANT — not surfaced in the UI. The project manager chases
     * homeowners and contractors herself, so the portal deliberately
     * offers no system nudge (decision 2026-09-01). Kept, with its tests,
     * so re-enabling is a one-line change in the checklist row; the
     * modal's "Asked for it" covers recording that she made contact.
     */
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

    // ── Waive / N/A / reopen / note (main-app parity) ─────────────

    public function openRequirementActions(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        $this->actingOnRequirementId = $req->id;
        $this->reqActionReason = '';
        $this->reqWorkerNote = (string) ($req->worker_note ?? '');
        $this->resetErrorBag(['reqActionReason', 'reqWorkerNote']);
    }

    /** Explicit Cancel is the only close path (portal modal convention). */
    public function closeRequirementActions(): void
    {
        $this->actingOnRequirementId = null;
        $this->reqActionReason = '';
        $this->reqWorkerNote = '';
        $this->resetErrorBag(['reqActionReason', 'reqWorkerNote']);
    }

    /**
     * The standing annotation on a requirement. Available in any status and
     * never changes the lifecycle — editing replaces it outright, unlike the
     * append-only `notes` audit column.
     */
    public function saveRequirementNote(): void
    {
        if (! $this->actingOnRequirementId) {
            return;
        }

        $req = $this->ownRequirement($this->actingOnRequirementId);

        $this->validate(['reqWorkerNote' => ['nullable', 'string', 'max:2000']]);

        $note = trim($this->reqWorkerNote);
        $req->worker_note = $note === '' ? null : $note;
        $req->updated_by_user_id = Auth::id();
        $req->save();

        $this->log->refresh();
        session()->flash('homes_message', 'Note saved.');
    }

    /**
     * Apply a status transition. action ∈ {requested, received, waived, na,
     * reopen}; waived and na require a reason, and reopen is the undo for a
     * requirement checked off by mistake.
     */
    public function applyRequirementAction(string $action): void
    {
        if (! $this->actingOnRequirementId) {
            return;
        }

        $req = $this->ownRequirement($this->actingOnRequirementId);
        $actor = Auth::user();

        switch ($action) {
            case 'requested':
                $req->markRequested($actor);
                break;

            case 'received':
                $req->markReceived($actor);
                break;

            case 'waived':
                if (trim($this->reqActionReason) === '') {
                    $this->addError('reqActionReason', 'A reason is required to waive this.');

                    return;
                }
                $req->markWaived($actor, trim($this->reqActionReason));
                break;

            case 'na':
                if (trim($this->reqActionReason) === '') {
                    $this->addError('reqActionReason', "A reason is required to mark this doesn't apply.");

                    return;
                }
                $req->markNotApplicable($actor, trim($this->reqActionReason));
                break;

            case 'reopen':
                $req->reopen($actor);
                break;

            default:
                abort(422, 'Unknown requirement action.');
        }

        // A transition may flip the Phase-1 gate — same as the main app.
        $req->fresh()->communicationLog?->applyAutoCategory();

        $this->closeRequirementActions();
        $this->log->refresh();
        session()->flash('homes_message', 'Requirement updated.');
    }

    // ── Attachments ───────────────────────────────────────────────

    /**
     * Same gate as the main app: removing an attachment is not part of the
     * project manager's day-to-day, so it needs delete rights or admin.
     */
    public function canRemoveFiles(): bool
    {
        return $this->isAdmin() || $this->can('communications_log.delete');
    }

    public function deleteRequirementMedia(int $requirementId, int $mediaId): void
    {
        if (! $this->canRemoveFiles()) {
            abort(403, 'You do not have permission to remove attachments.');
        }

        $req = IntakeRequirement::findOrFail($requirementId);

        if ($req->communication_log_id !== (int) $this->log->id) {
            abort(403);
        }

        $media = $req->getMedia('documents')->firstWhere('id', $mediaId);

        if ($media) {
            $media->delete();
            $this->log->refresh();
            session()->flash('homes_message', 'Attachment removed.');
        }
    }

    /** Mime types the media preview route can render inline. */
    protected const PREVIEWABLE_MIMES = [
        'application/pdf',
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'image/webp', 'image/svg+xml',
        'text/plain', 'text/csv',
    ];

    /** Normalized attachment rows for one requirement. */
    protected function fileRowsFor(IntakeRequirement $req): array
    {
        return $req->getMedia('documents')->map(fn ($m) => [
            'id' => $m->id,
            'requirement_id' => $req->id,
            'name' => MediaValidationService::displayName($m),
            'mime' => $m->mime_type,
            'size' => $m->human_readable_size,
            'uploaded' => $m->created_at,
            'preview_url' => route('media.preview', $m->id),
            'download_url' => route('media.download', $m->id),
            'previewable' => in_array($m->mime_type, self::PREVIEWABLE_MIMES, true),
        ])->values()->all();
    }

    // ── Edit the log itself ───────────────────────────────────────

    public function openEdit(): void
    {
        $this->authorizeAction('communications_log.edit');

        $this->edit = [
            'contact_first_name' => (string) ($this->log->contact_first_name ?? ''),
            'contact_last_name' => (string) ($this->log->contact_last_name ?? ''),
            'contact_phone' => (string) ($this->log->contact_phone ?? ''),
            'contact_email' => (string) ($this->log->contact_email ?? ''),
            'contact_address' => (string) ($this->log->contact_address ?? ''),
            'contact_city' => (string) ($this->log->contact_city ?? ''),
            'contact_state' => (string) ($this->log->contact_state ?? 'PA'),
            'contact_zip' => (string) ($this->log->contact_zip ?? ''),
            'contact_role' => (string) ($this->log->contact_role ?? ''),
            'subject' => (string) ($this->log->subject ?? ''),
            'summary' => (string) ($this->log->summary ?? ''),
            'notes' => (string) ($this->log->notes ?? ''),
            'priority' => (string) ($this->log->priority ?? 'normal'),
            'communication_type' => (string) ($this->log->communication_type ?? 'phone_call'),
            'direction' => (string) ($this->log->direction ?? 'outgoing'),
            'communication_date' => $this->log->communication_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
        ];
        $this->showEdit = true;
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->resetErrorBag();
    }

    public function saveEdit(): void
    {
        $this->authorizeAction('communications_log.edit');

        $validated = $this->validate([
            'edit.contact_first_name' => 'nullable|string|max:255',
            'edit.contact_last_name' => 'nullable|string|max:255',
            'edit.contact_phone' => 'nullable|string|max:50',
            'edit.contact_email' => 'nullable|email|max:255',
            'edit.contact_address' => 'nullable|string|max:255',
            'edit.contact_city' => 'nullable|string|max:100',
            'edit.contact_state' => 'nullable|string|max:2',
            'edit.contact_zip' => 'nullable|string|max:10',
            'edit.contact_role' => 'nullable|in:homeowner,contractor,inspector,vendor,agency,applicant,family_member,other',
            'edit.subject' => 'required|string|max:255',
            'edit.summary' => 'nullable|string',
            'edit.notes' => 'nullable|string',
            'edit.priority' => 'required|in:low,normal,high,urgent',
            'edit.communication_type' => 'required|in:phone_call,email,in_person,site_visit,video_call,text,mail,voicemail,fax,other',
            'edit.direction' => 'required|in:incoming,outgoing',
            'edit.communication_date' => 'required|date',
        ])['edit'];

        // Blank optional strings persist as null, matching the main app.
        $payload = collect($validated)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();
        $payload['subject'] = $validated['subject'];
        $payload['updated_by'] = Auth::id();

        $this->log->update($payload);

        $this->showEdit = false;
        $this->log->refresh();
        session()->flash('homes_message', 'Entry updated.');
    }

    // ── Timeline entries ("Every touch") ──────────────────────────
    //
    // The timeline used to be append-only, which meant a note typed onto
    // the wrong record stayed there for good. It is now correctable and
    // removable, under two rules:
    //
    //  * Entries the SYSTEM wrote about itself — requirement transitions,
    //    conversions, merges — are never reworded by anyone. They are
    //    statements of what happened; rewriting one would make the record
    //    claim something that never took place. An admin can remove one
    //    outright (the recycle-bin posture: it stops showing, it does not
    //    stop existing), the project manager cannot.
    //  * Everything a person typed is hers to fix or remove.
    //
    // Nothing is destroyed either way: a removal soft-deletes, and the
    // card offers the entry straight back.

    public function openEditEntry(int $entryId): void
    {
        $entry = $this->ownEntry($entryId);

        if ($entry->isSystemWritten()) {
            session()->flash('homes_error', 'This entry was written by the system when the record changed, so it cannot be reworded. It can be removed instead.');

            return;
        }

        $this->editingEntryId = $entry->id;
        $this->entryContent = (string) $entry->update_content;
        $this->entryType = array_key_exists((string) $entry->update_type, CommunicationLogUpdate::AUTHORED_TYPES)
            ? (string) $entry->update_type
            : 'note';
        $this->entryDate = $entry->update_date?->format('Y-m-d') ?? '';
        $this->resetErrorBag(['entryContent', 'entryType', 'entryDate']);
    }

    /** Explicit Cancel is the only close path (portal modal convention). */
    public function closeEditEntry(): void
    {
        $this->editingEntryId = null;
        $this->entryContent = '';
        $this->entryType = 'note';
        $this->entryDate = '';
        $this->resetErrorBag(['entryContent', 'entryType', 'entryDate']);
    }

    public function saveEntry(): void
    {
        if (! $this->editingEntryId) {
            return;
        }

        $entry = $this->ownEntry($this->editingEntryId);

        if ($entry->isSystemWritten()) {
            abort(403);
        }

        $this->validate([
            'entryContent' => ['required', 'string', 'min:1', 'max:10000'],
            'entryType' => ['required', 'in:'.implode(',', array_keys(CommunicationLogUpdate::AUTHORED_TYPES))],
            'entryDate' => ['nullable', 'date'],
        ]);

        $entry->update_content = trim($this->entryContent);
        $entry->update_type = $this->entryType;
        $entry->update_date = $this->entryDate ?: null;
        // Deliberately separate from updated_at, which moves for writes
        // nobody typed (a merge re-pointing the row, a backfill).
        $entry->edited_at = now();
        $entry->edited_by = Auth::id();
        $entry->save();

        $this->closeEditEntry();
        $this->log->refresh();
        session()->flash('homes_message', 'Entry updated.');
    }

    public function confirmRemoveEntry(int $entryId): void
    {
        $entry = $this->ownEntry($entryId);

        if ($entry->isSystemWritten() && ! $this->isAdmin()) {
            session()->flash('homes_error', 'This entry records something the system did. An admin can remove it.');

            return;
        }

        // One dialog at a time — removing replaces an open correction.
        $this->closeEditEntry();
        $this->removingEntryId = $entry->id;
    }

    public function cancelRemoveEntry(): void
    {
        $this->removingEntryId = null;
    }

    public function removeEntry(): void
    {
        if (! $this->removingEntryId) {
            return;
        }

        $entry = $this->ownEntry($this->removingEntryId);

        if ($entry->isSystemWritten() && ! $this->isAdmin()) {
            abort(403);
        }

        // deleted_by is stamped by TracksSoftDeleter.
        $entry->delete();

        $this->removingEntryId = null;
        $this->log->refresh();
        session()->flash('homes_message', 'Entry removed. It stays on the record — reopen "removed" below to put it back.');
    }

    public function restoreEntry(int $entryId): void
    {
        $entry = $this->ownEntry($entryId, withTrashed: true);

        if (! $entry->trashed()) {
            return;
        }

        $entry->restore();

        $this->log->refresh();
        session()->flash('homes_message', 'Entry restored to the timeline.');
    }

    public function toggleRemovedEntries(): void
    {
        $this->showRemovedEntries = ! $this->showRemovedEntries;
    }

    /**
     * An entry on THIS log, or a 403. Mirrors ownRequirement() — the id
     * arrives from the browser, so the parent is always re-checked.
     */
    protected function ownEntry(int $entryId, bool $withTrashed = false): CommunicationLogUpdate
    {
        $this->authorizeAction('communications_log.edit');

        $entry = CommunicationLogUpdate::query()
            ->when($withTrashed, fn ($q) => $q->withTrashed())
            ->findOrFail($entryId);

        if ((int) $entry->communication_log_id !== (int) $this->log->id) {
            abort(403);
        }

        return $entry;
    }

    /**
     * Detach a wrongly linked participant. A linked participant's name is
     * shown ahead of the contact name typed on the entry, so a bad link
     * makes name edits look as if they did not save.
     */
    public function unlinkParticipant(): void
    {
        $this->authorizeAction('communications_log.edit');

        if ($this->log->is_converted || ! $this->log->participant_id) {
            return;
        }

        $this->log->update([
            'participant_id' => null,
            'household_id' => null,
            'updated_by' => Auth::id(),
        ]);
        $this->log->refresh();

        session()->flash('homes_message', 'Participant unlinked — this entry now shows the name typed on it.');
    }

    // ── Counties (housing services) — attach/detach with requirement
    //    side effects, mirroring the main app's toggleHousingServiceOnLog ──

    public function toggleCounty(int $serviceId): void
    {
        $this->authorizeAction('communications_log.edit');

        $log = $this->log;
        $svc = HousingService::findOrFail($serviceId);
        $actor = Auth::user();

        $alreadyAttached = $log->housingServices()->where('housing_services.id', $serviceId)->exists();
        $serviceTag = 'service:'.$serviceId;

        if ($alreadyAttached) {
            $log->housingServices()->detach($serviceId);

            // Strip the tag and orphan auto-applied requirements that no
            // longer have any other source. Manual atom picks survive.
            $affected = IntakeRequirement::query()
                ->where('communication_log_id', $log->id)
                ->get();

            foreach ($affected as $req) {
                $via = is_array($req->via) ? $req->via : [];
                if (! in_array($serviceTag, $via, true)) {
                    continue;
                }

                $remaining = array_values(array_diff($via, [$serviceTag]));
                $req->via = $remaining;
                $req->updated_by_user_id = $actor->id;
                $req->save();

                if (empty($remaining) && in_array(
                    $req->source_type,
                    [IntakeRequirement::SOURCE_TEMPLATE_SET, IntakeRequirement::SOURCE_DEFINITION],
                    true
                ) && ! $req->is_orphaned) {
                    $req->markOrphaned($actor);
                }
            }

            session()->flash('homes_message', 'Removed "'.$svc->name.'" from this entry.');
        } else {
            $log->housingServices()->attach($serviceId, [
                'created_by_user_id' => $actor->id,
                'created_at' => now(),
            ]);
            app(IntakeRequirementInstantiator::class)->forHousingService($log, $svc, $actor);

            session()->flash('homes_message', 'Added "'.$svc->name.'" — fund codes and requirements have been attached.');
        }

        $log->fresh()->applyAutoCategory();
        $this->log->refresh();
    }

    // ── Ad-hoc requirement add ────────────────────────────────────

    public function saveAdHocRequirement(): void
    {
        $this->authorizeAction('communications_log.edit');

        $this->validate([
            'adHocLabel' => ['required', 'string', 'max:200'],
            'adHocType' => ['required', 'in:'.IntakeRequirement::TYPE_DOCUMENT.','.IntakeRequirement::TYPE_TASK],
        ]);

        app(IntakeRequirementInstantiator::class)->adHoc(
            $this->log,
            trim($this->adHocLabel),
            $this->adHocType,
            Auth::user()
        );

        $this->showAddReq = false;
        $this->adHocLabel = '';
        $this->adHocType = IntakeRequirement::TYPE_DOCUMENT;
        $this->log->refresh();
        session()->flash('homes_message', 'Requirement added.');
    }

    // ── File upload onto a document requirement ───────────────────

    public function openUpload(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        if ($req->type !== IntakeRequirement::TYPE_DOCUMENT) {
            abort(422, 'File uploads are only supported for document-type requirements.');
        }

        // Only one modal at a time — the upload dialog replaces the
        // actions dialog it can be launched from.
        $this->closeRequirementActions();

        $this->uploadingRequirementId = $req->id;
        $this->requirementFiles = [];
    }

    public function closeUpload(): void
    {
        $this->uploadingRequirementId = null;
        $this->requirementFiles = [];
    }

    public function uploadRequirementFiles(): void
    {
        if (! $this->uploadingRequirementId) {
            return;
        }

        $req = $this->ownRequirement($this->uploadingRequirementId);

        $this->validate([
            'requirementFiles' => ['required', 'array', 'min:1'],
            'requirementFiles.*' => ['file', 'max:102400'], // 100MB, matching the main app
        ]);

        if ($req->type !== IntakeRequirement::TYPE_DOCUMENT) {
            abort(422, 'File uploads are only supported for document-type requirements.');
        }

        foreach ($this->requirementFiles as $file) {
            $req->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName(MediaValidationService::sanitizeFileName($file->getClientOriginalName()))
                ->toMediaCollection('documents');
        }

        // Receiving a file naturally satisfies the requirement.
        if ($req->status !== IntakeRequirement::STATUS_RECEIVED) {
            $req->markReceived(Auth::user());
        }

        $req->fresh()->communicationLog?->applyAutoCategory();

        $this->closeUpload();
        $this->log->refresh();
        session()->flash('homes_message', 'File(s) uploaded and requirement marked received.');
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

    // ── Merge a duplicate entry into this one ─────────────────────

    /**
     * Merging ends with a record disappearing from her list, so it is gated
     * the same way deleting one is — and it rewrites this entry, so it needs
     * edit rights too.
     */
    public function canMergeDuplicates(): bool
    {
        return $this->can('communications_log.edit') && $this->can('communications_log.delete');
    }

    protected function authorizeMerge(): void
    {
        if (! $this->canMergeDuplicates()) {
            abort(403, 'You do not have permission to merge entries.');
        }
    }

    public function openMerge(): void
    {
        $this->authorizeMerge();

        $this->mergeStep = 1;
        $this->mergeSearch = '';
        $this->mergeDuplicateId = null;
        $this->resetErrorBag(['merge']);
        $this->showMerge = true;
    }

    /** Explicit Cancel is the only close path (portal modal convention). */
    public function closeMerge(): void
    {
        $this->showMerge = false;
        $this->mergeStep = 1;
        $this->mergeSearch = '';
        $this->mergeDuplicateId = null;
        $this->resetErrorBag(['merge']);
    }

    public function selectMergeCandidate(int $id, CommunicationLogMergeService $merger): void
    {
        $this->authorizeMerge();

        $duplicate = CommunicationLog::find($id);

        if ($duplicate === null) {
            $this->addError('merge', 'That entry could not be found.');

            return;
        }

        $blocked = $merger->blockedReason($this->log, $duplicate);

        if ($blocked !== null) {
            $this->addError('merge', $blocked);

            return;
        }

        $this->resetErrorBag(['merge']);
        $this->mergeDuplicateId = $duplicate->id;
        $this->mergeStep = 2;
    }

    public function backToMergePick(): void
    {
        $this->mergeDuplicateId = null;
        $this->mergeStep = 1;
        $this->resetErrorBag(['merge']);
    }

    public function doMerge(CommunicationLogMergeService $merger): void
    {
        $this->authorizeMerge();

        if (! $this->mergeDuplicateId) {
            return;
        }

        $duplicate = CommunicationLog::find($this->mergeDuplicateId);

        if ($duplicate === null) {
            $this->addError('merge', 'That entry could not be found.');

            return;
        }

        try {
            $summary = $merger->merge($this->log, $duplicate, Auth::user());
        } catch (\RuntimeException $e) {
            $this->addError('merge', $e->getMessage());

            return;
        }

        $this->closeMerge();
        $this->log->refresh();

        session()->flash('homes_message', sprintf(
            'Merged COMM-%d into this entry — %d timeline %s and %d %s moved over.',
            $summary['duplicate_id'],
            $summary['timeline_entries_moved'] + 1,
            $summary['timeline_entries_moved'] + 1 === 1 ? 'entry' : 'entries',
            $summary['requirements_moved'],
            $summary['requirements_moved'] === 1 ? 'requirement' : 'requirements',
        ));
    }

    /** Suggested duplicates plus anything her search turns up. */
    protected function mergeCandidateRows(CommunicationLogMergeService $merger, HousingPortalData $data): array
    {
        $needle = trim($this->mergeSearch);

        $logs = $needle !== ''
            ? CommunicationLog::search($needle)
                ->where('id', '!=', $this->log->id)
                ->whereNull('merged_into_id')
                ->with('participant')
                ->orderByDesc('communication_date')
                ->limit(15)
                ->get()
            : $merger->findCandidates($this->log);

        return $logs->map(fn (CommunicationLog $c) => [
            'id' => $c->id,
            'who' => $data->personName($c),
            'where' => $data->personWhere($c),
            'when' => $c->communication_date?->isoFormat('MMM D, YYYY'),
            'subject' => (string) $c->subject,
            'phone' => (string) $c->contact_phone,
            'blocked' => $merger->blockedReason($this->log, $c),
        ])->values()->all();
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

    public function render(HousingPortalData $data, ConvertIntakeToProject $convert, HousingRecordDeleter $deleter, CommunicationLogMergeService $merger)
    {
        $log = $this->log->load([
            'participant', 'property',
            'updates.createdBy', 'updates.editedBy',
            'intakeRequirements' => fn ($q) => $q->where('is_orphaned', false),
            'intakeRequirements.definition',
            'intakeRequirements.media',
        ]);

        $canEditLog = $this->can('communications_log.edit');
        $isAdmin = $this->isAdmin();

        // "Every touch" — updates plus a synthesized root entry for the
        // original call, newest first.
        $timeline = $log->updates->map(fn ($u) => [
            'id' => $u->id,
            'root' => false,
            'on' => $u->created_at,
            'kind' => $u->update_type_display,
            'color' => $u->update_type_color,
            'note' => (string) $u->update_content,
            'by' => $u->createdBy?->name,
            'due' => $u->update_date,
            // The system's own entries are a record of what happened, so
            // they are never reworded and only an admin may remove one.
            'system' => $u->isSystemWritten(),
            'edited_by' => $u->wasEdited() ? ($u->editedBy?->name ?: 'someone') : null,
            'edited_on' => $u->edited_at,
            'can_edit' => $canEditLog && ! $u->isSystemWritten(),
            'can_remove' => $canEditLog && (! $u->isSystemWritten() || $isAdmin),
        ]);

        // The original call is not a row of its own — it IS the log, so it
        // is corrected through the entry editor and removed by deleting the
        // whole record (which has its own ordering rule).
        $timeline->push([
            'id' => null,
            'root' => true,
            'on' => $log->communication_date,
            'kind' => ucfirst(str_replace('_', ' ', (string) $log->communication_type)).' · '.ucfirst((string) $log->direction),
            'color' => 'gray',
            'note' => (string) ($log->summary ?: $log->subject),
            'by' => null,
            'due' => null,
            'system' => false,
            'edited_by' => null,
            'edited_on' => null,
            'can_edit' => $canEditLog,
            'can_remove' => false,
        ]);

        $timeline = $timeline->sortByDesc('on')->values();

        // Removed entries stay on the record. Everyone who can read the
        // call can see that something was taken off it; putting it back
        // needs the same permission that took it off.
        $removedEntries = $this->showRemovedEntries
            ? $log->updates()->onlyTrashed()->with(['createdBy', 'deletedBy'])->get()->map(fn ($u) => [
                'id' => $u->id,
                'on' => $u->created_at,
                'kind' => $u->update_type_display,
                'note' => (string) $u->update_content,
                'by' => $u->createdBy?->name,
                'removed_by' => $u->deletedBy?->name,
                'removed_on' => $u->deleted_at,
            ])->values()
            : collect();

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
            // The income row opens the calculator instead of a plain
            // "Received" — the figures are the substance of it.
            'is_income' => $r->isIncomeEligibility(),
            'files' => $this->fileRowsFor($r),
        ])->values();

        // Every attachment on the entry, newest first — the portal's answer
        // to the main app's "Documents on file" table.
        $documentRows = $requirements
            ->flatMap(fn (array $r) => array_map(
                fn (array $f) => $f + ['source' => $r['label']],
                $r['files']
            ))
            ->sortByDesc(fn (array $f) => $f['uploaded']?->getTimestamp() ?? 0)
            ->values();

        $gate = $convert->gate($log);
        $progress = $data->phase1Progress($log);
        $stage = $data->stage($log);

        $attachedCountyIds = $log->housingServices()->pluck('housing_services.id')->all();

        return view('livewire.homes.call-detail', [
            'who' => $data->personName($log),
            'where' => $data->personWhere($log),
            'stage' => $stage,
            'timeline' => $timeline,
            'requirements' => $requirements,
            'gate' => $gate,
            'progress' => $progress,
            // Convert is available on ANY non-converted entry (the modal
            // carries the requirements gate + override), matching the main app.
            'canConvert' => ! $log->is_converted && $this->can('projects.create'),
            'canEdit' => $canEditLog,
            'canDelete' => $this->can('communications_log.delete'),
            'removedEntries' => $removedEntries,
            'removedCount' => $log->updates()->onlyTrashed()->count(),
            'entryTypes' => CommunicationLogUpdate::AUTHORED_TYPES,
            'removingEntry' => $this->removingEntryId
                ? $timeline->firstWhere('id', $this->removingEntryId)
                : null,
            'documentRows' => $documentRows,
            'canRemoveFiles' => $this->canRemoveFiles(),
            'actingOn' => $this->actingOnRequirementId
                ? $requirements->firstWhere('id', $this->actingOnRequirementId)
                : null,
            // Non-null when a live project still hangs off this call — the
            // button stays visible but explains what has to happen first.
            'deleteBlocked' => $deleter->callBlockedReason($log),
            'canMerge' => $this->canMergeDuplicates(),
            'mergeCandidates' => $this->showMerge && $this->mergeStep === 1
                ? $this->mergeCandidateRows($merger, $data)
                : [],
            'mergeDuplicate' => $this->mergeDuplicateId
                ? CommunicationLog::find($this->mergeDuplicateId)
                : null,
            'mergePreview' => $this->mergeDuplicateId && ($dup = CommunicationLog::find($this->mergeDuplicateId))
                ? $merger->preview($log, $dup)
                : null,
            // Duplicates already folded into this entry — shown so the record
            // says outright that it is the union of more than one call.
            'mergedFrom' => $log->mergedFrom()->get(['id', 'contact_first_name', 'contact_last_name', 'merged_at']),
            'counties' => HousingService::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'attachedCountyIds' => $attachedCountyIds,
            'incomeAssessment' => $log->incomeAssessment()->first(),
            'incomeDocGroups' => $this->showIncomeModal ? $this->incomeDocumentGroups() : [],
            'incomeSourceTypes' => IntakeIncomeAssessment::SOURCE_TYPES,
            'incomeDocTypes' => IntakeIncomeAssessment::DOC_TYPES,
        ]);
    }
}
