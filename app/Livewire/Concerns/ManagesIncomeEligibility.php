<?php

namespace App\Livewire\Concerns;

use App\Models\CommunicationLog;
use App\Models\IntakeIncomeAssessment;
use App\Models\IntakeRequirement;
use App\Models\PovertyGuideline;
use App\Services\Intake\IncomeEligibilityCalculator;
use App\Services\MediaValidationService;
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

    /**
     * A guideline year the determination of record is being held up
     * against. Read-only and request-scoped: it changes what is on
     * screen, never what is stored.
     */
    public ?int $incomeCompareYear = null;

    /** Per-request memo for currentIncomeAssessment(). */
    private ?IntakeIncomeAssessment $loadedIncomeAssessment = null;

    private bool $incomeAssessmentLoaded = false;

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
        $assessment = $this->currentIncomeAssessment();

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

        return $calc->assess(
            $annual,
            $this->incomeForm['household_size'] ?? null,
            $this->incomePinnedYear(),
            $this->incomeGuidelineRegion()
        );
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

        // A saved determination is re-run against the year it was taken
        // against, never silently against the newest table loaded —
        // see incomePinnedYear().
        $pinnedYear = $this->incomePinnedYear();

        $assessed = $calc->assess(
            $annual,
            $this->incomeForm['household_size'],
            $pinnedYear,
            $this->incomeGuidelineRegion()
        );
        $requirement = $this->incomeRequirement();
        $actor = Auth::user();

        $assessment = $this->currentIncomeAssessment()
            ?? new IntakeIncomeAssessment(['communication_log_id' => $log->id]);
        $isNew = ! $assessment->exists;

        $assessment->fill([
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
            'area_median_income' => $assessed['ami'],
            'poverty_percentage' => $assessed['percentage'],
            'poverty_bracket' => $assessed['bracket'],
            'calculated_at' => now(),
            'notes' => trim((string) $this->incomeForm['notes']) ?: null,
            'updated_by_user_id' => $actor?->id,
        ]);

        // The original author stays the original author; only the
        // editor moves. Rewriting created_by on every edit would make
        // the record claim the last person to touch it took the
        // determination.
        if ($isNew) {
            $assessment->created_by_user_id = $actor?->id;
        }

        $assessment->save();
        $this->forgetIncomeAssessment();
        $this->incomeCompareYear = null;

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

        $message = $assessed['available']
            ? 'Income eligibility saved — '.$assessed['label'].'.'
            : 'Income eligibility saved. No income guidelines are loaded for this household size yet, so no percentage was calculated.';

        // Editing the figures must not quietly move a household onto a
        // newer table, so say plainly that it did not.
        if ($pinnedYear !== null && $assessed['available']) {
            $newest = PovertyGuideline::latestYear($assessed['guideline_region']);

            if ($newest !== null && (int) $newest !== (int) $assessed['guideline_year']) {
                $message .= ' Kept on the '.$assessed['guideline_year']
                    .' guidelines it was originally determined against.';
            }
        }

        session()->flash($this->incomeFlashKey(), $message);
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
                ->usingFileName(MediaValidationService::sanitizeFileName($file->getClientOriginalName()))
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

    /**
     * The saved assessment for the current intake, if any. Memoized for
     * the request — the live preview re-reads it on every keystroke.
     */
    public function currentIncomeAssessment(): ?IntakeIncomeAssessment
    {
        if (! $this->incomeAssessmentLoaded) {
            $this->loadedIncomeAssessment = $this->incomeEligibilityLog()?->incomeAssessment()->first();
            $this->incomeAssessmentLoaded = true;
        }

        return $this->loadedIncomeAssessment;
    }

    /** Drop the memo after a write. */
    protected function forgetIncomeAssessment(): void
    {
        $this->loadedIncomeAssessment = null;
        $this->incomeAssessmentLoaded = false;
    }

    /**
     * The guideline year an existing determination is measured against.
     *
     * A determination is a SNAPSHOT: HHS republishes the poverty
     * guidelines every January and HUD reissues the HOME limits every
     * year, and a household qualified against FY2026 has to keep
     * reading that way. So editing the figures on a saved
     * determination re-runs them against the same year — correcting a
     * pay stub must not silently restate what the household was
     * qualified at. Moving a determination onto a newer year is a
     * re-determination: a decision someone makes deliberately.
     *
     * Null for a first determination, which resolves to the newest year
     * loaded. Null too when the row carries no year, and harmless when
     * the dataset itself has changed underneath (a log that has since
     * gained a housing service reads the HOME limits now) — the
     * calculator falls forward to that region's newest year on its own.
     */
    protected function incomePinnedYear(): ?int
    {
        $year = $this->currentIncomeAssessment()?->guideline_year;

        return $year ? (int) $year : null;
    }

    // ── Checking the figures against another guideline year ────────

    /**
     * Years this determination could be held up against: every year
     * loaded for the dataset it was measured against, minus the one of
     * record. Empty when there is nothing to compare to, so the control
     * stays off the screen instead of offering a list of one.
     *
     * @return array<int, int>
     */
    public function incomeComparisonYears(): array
    {
        $assessment = $this->currentIncomeAssessment();

        if ($assessment === null || ! $assessment->isComplete()) {
            return [];
        }

        $ofRecord = (int) $assessment->guideline_year;

        return array_values(array_filter(
            PovertyGuideline::yearsFor($assessment->guideline_region),
            fn (int $year) => $year !== $ofRecord
        ));
    }

    /**
     * Hold the recorded figures up against another year's guidelines.
     *
     * Deliberately READ-ONLY: nothing is written, the determination of
     * record does not move, and the answer goes away when it is closed.
     * It answers "what would this household look like under the FY2027
     * limits?" without restating what they were qualified at.
     */
    public function compareIncomeYear($year): void
    {
        $this->authorizeAction('communications_log.view');

        $year = (int) $year;

        $this->incomeCompareYear = in_array($year, $this->incomeComparisonYears(), true)
            ? $year
            : null;
    }

    public function clearIncomeComparison(): void
    {
        $this->incomeCompareYear = null;
    }

    /**
     * The recorded income and household size run against
     * $incomeCompareYear — same figures, same dataset, different year.
     *
     * The region comes off the assessment rather than the live intake:
     * moving the year and the dataset at once would answer a question
     * nobody asked.
     */
    public function getIncomeComparisonProperty(): ?array
    {
        $assessment = $this->currentIncomeAssessment();

        if ($this->incomeCompareYear === null || $assessment === null || ! $assessment->isComplete()) {
            return null;
        }

        return app(IncomeEligibilityCalculator::class)->assess(
            $assessment->annual_income,
            $assessment->household_size,
            $this->incomeCompareYear,
            $assessment->guideline_region
        );
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
