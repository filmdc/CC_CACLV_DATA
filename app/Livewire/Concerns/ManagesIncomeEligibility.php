<?php

namespace App\Livewire\Concerns;

use App\Models\CommunicationLog;
use App\Models\IntakeIncomeAssessment;
use App\Models\IntakeRequirement;
use App\Models\PovertyGuideline;
use App\Services\Intake\IncomeEligibilityCalculator;
use Illuminate\Support\Facades\Auth;

/**
 * The income-eligibility calculator, shared by both front doors onto an
 * intake: the main app's Communications Log viewer and the homes
 * portal's Call detail. Behaviour is identical in both; only the
 * modal's markup differs (dark app chrome vs. the light CALV theme).
 *
 * The consuming component supplies the intake being worked on via
 * incomeEligibilityLog(); everything else — validation, the live
 * percent-of-poverty preview, persistence, the document slots, and the
 * requirement transition — lives here so the two surfaces cannot drift.
 *
 * Requires WithFileUploads and WithPermissionChecks on the host
 * component (both already use them).
 */
trait ManagesIncomeEligibility
{
    public bool $showIncomeModal = false;

    /** @var array{household_size: string|int, entry_period: string, entered_amount: string|float, notes: string} */
    public array $incomeForm = [
        'household_size' => '',
        'entry_period' => 'annual',
        'entered_amount' => '',
        'notes' => '',
    ];

    /** Itemized lines: [['type' => 'wages', 'label' => '', 'amount' => '']]. */
    public array $incomeSources = [];

    /** Which proof slot the file picker is currently filling. */
    public string $incomeDocSlot = 'pay_stub';

    public array $incomeDocFiles = [];

    /** The intake this calculator is operating on. */
    abstract protected function incomeEligibilityLog(): ?CommunicationLog;

    /** Session key for user-facing confirmations; portals override. */
    protected function incomeFlashKey(): string
    {
        return 'success';
    }

    /**
     * Which guideline dataset this intake qualifies against. Housing
     * intakes — any log with a housing service attached — are measured
     * against the HUD HOME income limits; everything else reads the
     * HHS federal poverty guidelines. Derived from the intake itself
     * (not the surface), so both front doors can never disagree.
     */
    protected function incomeGuidelineRegion(): string
    {
        $log = $this->incomeEligibilityLog();

        if ($log !== null && $log->housingServices()->exists()) {
            return PovertyGuideline::REGION_HOME_ABE;
        }

        return IncomeEligibilityCalculator::REGION_DEFAULT;
    }

    // ── Opening / closing ─────────────────────────────────────────

    public function openIncomeEligibility(): void
    {
        $this->authorizeAction('communications_log.edit');

        $log = $this->incomeEligibilityLog();
        if ($log === null) {
            return;
        }

        $calc = app(IncomeEligibilityCalculator::class);
        $assessment = $log->incomeAssessment()->first();

        if ($assessment !== null) {
            $this->incomeForm = [
                'household_size' => $assessment->household_size ?? $this->defaultHouseholdSize($log),
                'entry_period' => $assessment->entry_period ?: 'annual',
                'entered_amount' => $assessment->entered_amount !== null
                    ? (string) (float) $assessment->entered_amount
                    : '',
                'notes' => (string) ($assessment->notes ?? ''),
            ];
            $this->incomeSources = array_map(
                fn ($line) => [
                    'type' => $line['type'] ?? 'other',
                    'label' => (string) ($line['label'] ?? ''),
                    'amount' => (string) ($line['amount'] ?? ''),
                ],
                $assessment->income_sources ?? []
            );
        } else {
            $this->incomeForm = [
                'household_size' => $this->defaultHouseholdSize($log),
                'entry_period' => 'annual',
                'entered_amount' => '',
                'notes' => '',
            ];
            $this->incomeSources = [];
        }

        if (empty($this->incomeSources)) {
            $this->incomeSources = [$calc->blankSource()];
        }

        $this->incomeDocSlot = 'pay_stub';
        $this->incomeDocFiles = [];
        $this->resetErrorBag($this->incomeErrorKeys());
        $this->showIncomeModal = true;
    }

    public function closeIncomeEligibility(): void
    {
        $this->showIncomeModal = false;
        $this->incomeDocFiles = [];
        $this->resetErrorBag($this->incomeErrorKeys());
    }

    // ── Itemized income lines ─────────────────────────────────────

    public function addIncomeSource(): void
    {
        $this->incomeSources[] = app(IncomeEligibilityCalculator::class)->blankSource('other');
    }

    public function removeIncomeSource(int $index): void
    {
        unset($this->incomeSources[$index]);
        $this->incomeSources = array_values($this->incomeSources);

        if (empty($this->incomeSources)) {
            $this->incomeSources = [app(IncomeEligibilityCalculator::class)->blankSource()];
        }
    }

    // ── Live preview ──────────────────────────────────────────────

    /**
     * Recomputed on every keystroke the modal binds live, so the worker
     * watches the percent of poverty move as she types. Nothing here
     * touches the database.
     */
    public function getIncomePreviewProperty(): array
    {
        $calc = app(IncomeEligibilityCalculator::class);

        $annual = $calc->annualTotal(
            $this->incomeSources,
            $this->incomeForm['entered_amount'] ?? null,
            $this->incomeForm['entry_period'] ?? 'annual'
        );

        return $calc->assess($annual, $this->incomeForm['household_size'] ?? null, null, $this->incomeGuidelineRegion());
    }

    // ── Persistence ───────────────────────────────────────────────

    public function saveIncomeEligibility(): void
    {
        $this->authorizeAction('communications_log.edit');

        $log = $this->incomeEligibilityLog();
        if ($log === null) {
            return;
        }

        $this->validate([
            'incomeForm.household_size' => ['required', 'integer', 'min:1', 'max:30'],
            'incomeForm.entry_period' => ['required', 'in:monthly,annual'],
            'incomeForm.entered_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'incomeForm.notes' => ['nullable', 'string', 'max:2000'],
            'incomeSources' => ['array'],
            'incomeSources.*.type' => ['nullable', 'string', 'max:40'],
            'incomeSources.*.label' => ['nullable', 'string', 'max:120'],
            'incomeSources.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ], [], [
            'incomeForm.household_size' => 'household size',
            'incomeForm.entered_amount' => 'income amount',
        ]);

        $calc = app(IncomeEligibilityCalculator::class);
        $sources = $calc->normalizeSources($this->incomeSources);
        $entered = $this->incomeForm['entered_amount'];
        $period = $this->incomeForm['entry_period'];

        $annual = $calc->annualTotal($sources, $entered, $period);

        if ($annual === null) {
            $this->addError(
                'incomeForm.entered_amount',
                'Enter the household income — either a total or at least one income line.'
            );

            return;
        }

        $assessed = $calc->assess($annual, $this->incomeForm['household_size'], null, $this->incomeGuidelineRegion());
        $requirement = $this->incomeRequirement();
        $actor = Auth::user();

        $assessment = IntakeIncomeAssessment::updateOrCreate(
            ['communication_log_id' => $log->id],
            [
                'intake_requirement_id' => $requirement?->id,
                'household_size' => (int) $this->incomeForm['household_size'],
                'entry_period' => $period,
                'income_sources' => $sources,
                // Only meaningful when nothing was itemized; kept so
                // reopening the form shows what she actually typed.
                'entered_amount' => empty($sources) && is_numeric($entered) ? round((float) $entered, 2) : null,
                'monthly_income' => $assessed['monthly_income'],
                'annual_income' => $assessed['annual_income'],
                'guideline_year' => $assessed['guideline_year'],
                'guideline_region' => $assessed['guideline_region'],
                'poverty_threshold' => $assessed['threshold'],
                'poverty_percentage' => $assessed['percentage'],
                'poverty_bracket' => $assessed['bracket'],
                'calculated_at' => now(),
                'notes' => trim((string) $this->incomeForm['notes']) ?: null,
                'created_by_user_id' => $actor?->id,
                'updated_by_user_id' => $actor?->id,
            ]
        );

        // Capturing the figures is what satisfies the requirement; the
        // proof documents are collected on the same row and can lag.
        if ($requirement !== null
            && $actor !== null
            && $assessment->isComplete()
            && $requirement->status !== IntakeRequirement::STATUS_RECEIVED) {
            $requirement->markReceived($actor);
        }

        $log->fresh()?->applyAutoCategory();
        $this->afterIncomeEligibilitySaved();

        $this->showIncomeModal = false;
        $this->incomeDocFiles = [];

        session()->flash(
            $this->incomeFlashKey(),
            $assessed['available']
                ? 'Income eligibility saved — '.$assessed['label'].'.'
                : 'Income eligibility saved. No income guidelines are loaded for this household size yet, so no percentage was calculated.'
        );
    }

    // ── Proof documents (pay stubs / tax return / benefit letters) ──

    public function uploadIncomeDocuments(): void
    {
        $this->authorizeAction('communications_log.edit');

        $requirement = $this->incomeRequirement();
        if ($requirement === null) {
            return;
        }

        $this->validate([
            'incomeDocSlot' => ['required', 'in:'.implode(',', array_keys(IntakeIncomeAssessment::DOC_TYPES))],
            'incomeDocFiles' => ['required', 'array', 'min:1'],
            'incomeDocFiles.*' => ['file', 'max:102400'], // 100MB, matching the rest of the app
        ]);

        foreach ($this->incomeDocFiles as $file) {
            $requirement->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties(['income_doc_type' => $this->incomeDocSlot])
                ->toMediaCollection('documents');
        }

        $count = count($this->incomeDocFiles);
        $this->incomeDocFiles = [];
        $this->afterIncomeEligibilitySaved();

        session()->flash(
            $this->incomeFlashKey(),
            $count.' '.IntakeIncomeAssessment::docTypeLabel($this->incomeDocSlot)
                .' file'.($count === 1 ? '' : 's').' attached.'
        );
    }

    /**
     * Attachments on the income requirement, grouped into the proof
     * slots. Files uploaded through the generic requirement uploader
     * carry no slot tag and fall into "Other income proof".
     *
     * @return array<string, array{label: string, files: array<int, array<string, mixed>>}>
     */
    public function incomeDocumentGroups(): array
    {
        $groups = [];
        foreach (IntakeIncomeAssessment::DOC_TYPES as $key => $label) {
            $groups[$key] = ['label' => $label, 'files' => []];
        }

        $requirement = $this->incomeRequirement();
        if ($requirement === null) {
            return $groups;
        }

        foreach ($requirement->getMedia('documents') as $media) {
            $slot = $media->getCustomProperty('income_doc_type');
            if (! array_key_exists($slot, $groups)) {
                $slot = 'other';
            }

            $groups[$slot]['files'][] = [
                'id' => $media->id,
                'name' => $media->name,
                'size' => $this->formatIncomeFileSize((int) $media->size),
                'preview_url' => route('media.preview', $media->id),
                'download_url' => route('media.download', $media->id),
            ];
        }

        return $groups;
    }

    /** The saved assessment for the current intake, if any. */
    public function currentIncomeAssessment(): ?IntakeIncomeAssessment
    {
        return $this->incomeEligibilityLog()?->incomeAssessment()->first();
    }

    /** The standing income-eligibility checklist row for this intake. */
    protected function incomeRequirement(): ?IntakeRequirement
    {
        $log = $this->incomeEligibilityLog();
        if ($log === null) {
            return null;
        }

        return IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
            ->first();
    }

    /** Hook for hosts that cache the log/requirements between renders. */
    protected function afterIncomeEligibilitySaved(): void
    {
        //
    }

    /** @return array<int, string> */
    protected function incomeErrorKeys(): array
    {
        return [
            'incomeForm.household_size',
            'incomeForm.entry_period',
            'incomeForm.entered_amount',
            'incomeForm.notes',
            'incomeSources',
            'incomeDocFiles',
            'incomeDocSlot',
        ];
    }

    /**
     * Prefill from the linked household when the intake already points
     * at one — the worker can always override, since the household on
     * file may be stale by the time she is on the phone.
     */
    protected function defaultHouseholdSize(CommunicationLog $log): string
    {
        $size = $log->household?->household_size
            ?? $log->participant?->household?->household_size;

        return $size ? (string) $size : '';
    }

    private function formatIncomeFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
