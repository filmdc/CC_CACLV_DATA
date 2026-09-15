<?php

namespace App\Livewire\Homes\Settings;

use App\Models\PovertyGuideline;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * HUD HOME income limits (homes.caclv.org settings) — the ceiling that
 * decides who qualifies for housing help.
 *
 * The dataset lives in poverty_guidelines under region
 * PovertyGuideline::REGION_HOME_ABE, alongside the HHS federal poverty
 * guidelines the rest of CAP TRACK measures against. Only the 80% AMI
 * ceiling is a real HUD figure; the monthly row and the bracket columns
 * are derived, so this screen edits ONE number per household size and
 * lets PovertyGuideline::upsertHomeCeiling() shape the rest — the same
 * method HomeIncomeLimitsSeeder calls.
 *
 * HUD reissues the limits every year, so the yearly job is "add FY next
 * year, copy last year's figures forward, type over the eight numbers".
 * Determinations already recorded are snapshots on
 * intake_income_assessments and are NOT rewritten by editing here.
 *
 * The FPL guidelines for non-housing intakes stay in the main app's
 * PovertyGuidelineManagement — this surface is HOME-only on purpose.
 */
#[Layout('components.layouts.homes')]
class IncomeLimits extends Component
{
    use WithPermissionChecks;

    /** HUD publishes the HOME limits for household sizes 1 through 8. */
    public const PUBLISHED_SIZES = 8;

    /** Household sizes to preview the over-8 extrapolation for. */
    private const PREVIEW_SIZES = [9, 10];

    #[Url]
    public ?int $year = null;

    /** family size => annual ceiling, as typed. */
    public array $ceilings = [];

    /**
     * HUD's published 4-person median family income for the year, as
     * typed. Blank means "derive it from the 4-person ceiling", which is
     * exact unless HUD capped the 80% limit that year.
     */
    public string $mfi = '';

    public bool $editing = false;

    /** Set while adding a year that has not been saved yet. */
    public bool $unsavedYear = false;

    // ── Add-year modal ────────────────────────────────────────────
    public bool $showAddYear = false;

    public ?int $newYear = null;

    public ?int $copyFromYear = null;

    public function mount(): void
    {
        $this->authorizeAction('poverty-guidelines.view');

        $years = PovertyGuideline::homeYears();
        if (! in_array($this->year, $years, true)) {
            $this->year = $years[0] ?? null;
        }

        $this->loadYear();
    }

    // ── Reading ───────────────────────────────────────────────────

    public function updatedYear(): void
    {
        $this->editing = false;
        $this->unsavedYear = false;
        $this->resetErrorBag();
        $this->loadYear();
    }

    protected function loadYear(): void
    {
        if ($this->year === null) {
            $this->ceilings = [];
            $this->mfi = '';

            return;
        }

        $stored = PovertyGuideline::homeCeilings((int) $this->year);

        // Always offer HUD's published block (1-8); keep any larger size
        // someone has stored, since thresholdFor() prefers a real row
        // over the extrapolation and hiding it would hide a live figure.
        $sizes = array_unique(array_merge(
            range(1, self::PUBLISHED_SIZES),
            array_map('intval', array_keys($stored))
        ));
        sort($sizes);

        $this->ceilings = [];
        foreach ($sizes as $size) {
            $this->ceilings[$size] = isset($stored[$size])
                ? $this->trimZeros($stored[$size])
                : '';
        }

        $year = (int) $this->year;
        $this->mfi = PovertyGuideline::homeMfiIsDerived($year)
            ? ''
            : $this->trimZeros((float) PovertyGuideline::homeMedianFamilyIncome($year));
    }

    /**
     * The median the form currently implies — the typed override when
     * there is one, otherwise derived from the typed 4-person ceiling.
     * Drives the live AMI column while editing.
     */
    protected function effectiveMfi(): ?float
    {
        if (is_numeric($this->mfi) && (float) $this->mfi > 0) {
            return (float) $this->mfi;
        }

        $four = $this->ceilings[4] ?? null;

        return is_numeric($four) ? PovertyGuideline::deriveHomeMfi((float) $four) : null;
    }

    /** @return array<int, int> */
    protected function sizes(): array
    {
        $sizes = array_map('intval', array_keys($this->ceilings));
        sort($sizes);

        return $sizes;
    }

    /** 58450.00 reads as 58450 in an input box. */
    protected function trimZeros(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    // ── Editing ───────────────────────────────────────────────────

    public function startEdit(): void
    {
        $this->authorizeAction('poverty-guidelines.edit');
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->resetErrorBag();

        if ($this->unsavedYear) {
            // The year was never written — fall back to a real one.
            $this->unsavedYear = false;
            $years = PovertyGuideline::homeYears();
            $this->year = $years[0] ?? null;
        }

        $this->loadYear();
    }

    public function save(): void
    {
        $this->authorizeAction('poverty-guidelines.edit');

        if ($this->year === null) {
            return;
        }

        $rules = [];
        $attributes = [];
        foreach ($this->sizes() as $size) {
            $rules['ceilings.'.$size] = 'required|numeric|min:0|max:100000000';
            $attributes['ceilings.'.$size] = $size.'-person limit';
        }

        // Every published size must carry a figure: thresholdFor()
        // treats a missing 1-8 row as "this year isn't loaded" and
        // returns null rather than extrapolating, which would silently
        // read as "no guidelines loaded" on a live intake.
        $rules['mfi'] = 'nullable|numeric|min:0|max:100000000';
        $attributes['mfi'] = 'median family income';

        $this->validate($rules, [], $attributes);

        foreach ($this->sizes() as $size) {
            PovertyGuideline::upsertHomeCeiling(
                (int) $this->year,
                $size,
                (float) $this->ceilings[$size]
            );
        }

        // Blank clears the override and goes back to deriving.
        PovertyGuideline::setHomeMedianFamilyIncome(
            (int) $this->year,
            is_numeric($this->mfi) && (float) $this->mfi > 0 ? (float) $this->mfi : null
        );

        $this->editing = false;
        $this->unsavedYear = false;
        $this->loadYear();

        session()->flash('homes_message', 'FY'.$this->year.' HOME limits saved. Determinations already recorded keep the figures they were made with.');
    }

    // ── Adding a year ─────────────────────────────────────────────

    public function openAddYear(): void
    {
        $this->authorizeAction('poverty-guidelines.create');

        $years = PovertyGuideline::homeYears();
        $this->newYear = ($years[0] ?? (int) date('Y')) + 1;
        $this->copyFromYear = $years[0] ?? null;
        $this->resetErrorBag();
        $this->showAddYear = true;
    }

    public function closeAddYear(): void
    {
        $this->showAddYear = false;
        $this->resetErrorBag();
    }

    /**
     * Stages the new year in the form — nothing is written until Save,
     * so a mistyped year never leaves a half-built block behind.
     */
    public function addYear(): void
    {
        $this->authorizeAction('poverty-guidelines.create');

        $this->validate([
            'newYear' => 'required|integer|min:2020|max:2040',
            'copyFromYear' => 'nullable|integer',
        ], [], [
            'newYear' => 'year',
            'copyFromYear' => 'year to copy from',
        ]);

        if (in_array((int) $this->newYear, PovertyGuideline::homeYears(), true)) {
            $this->addError('newYear', 'FY'.$this->newYear.' is already loaded — pick it from the year list to edit it.');

            return;
        }

        $source = $this->copyFromYear
            ? PovertyGuideline::homeCeilings((int) $this->copyFromYear)
            : [];

        $this->year = (int) $this->newYear;
        $this->ceilings = [];
        foreach (range(1, self::PUBLISHED_SIZES) as $size) {
            $this->ceilings[$size] = isset($source[$size]) ? $this->trimZeros($source[$size]) : '';
        }

        $this->mfi = ($this->copyFromYear && ! PovertyGuideline::homeMfiIsDerived((int) $this->copyFromYear))
            ? $this->trimZeros((float) PovertyGuideline::homeMedianFamilyIncome((int) $this->copyFromYear))
            : '';

        $this->showAddYear = false;
        $this->editing = true;
        $this->unsavedYear = true;

        session()->flash('homes_message', $this->copyFromYear
            ? 'Copied FY'.$this->copyFromYear.' forward — type over the figures from HUD\'s FY'.$this->year.' table, then save.'
            : 'Enter HUD\'s FY'.$this->year.' figures, then save.');
    }

    public function deleteYear(): void
    {
        $this->authorizeAction('poverty-guidelines.delete');

        if ($this->year === null) {
            return;
        }

        $removed = (int) $this->year;

        PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)
            ->where('year', $removed)
            ->delete();

        PovertyGuideline::setHomeMedianFamilyIncome($removed, null);

        $years = PovertyGuideline::homeYears();
        $this->year = $years[0] ?? null;
        $this->editing = false;
        $this->unsavedYear = false;
        $this->loadYear();

        session()->flash('homes_message', 'FY'.$removed.' HOME limits removed.');
    }

    public function render()
    {
        $years = PovertyGuideline::homeYears();

        // A staged year isn't in the table yet but must stay selectable.
        if ($this->year !== null && ! in_array((int) $this->year, $years, true)) {
            $years[] = (int) $this->year;
            rsort($years);
        }

        $mfi = $this->effectiveMfi();

        $rows = [];
        foreach ($this->sizes() as $size) {
            $annual = is_numeric($this->ceilings[$size]) ? (float) $this->ceilings[$size] : null;
            $rows[] = [
                'size' => $size,
                'annual' => $annual,
                'monthly' => $annual === null ? null : round($annual / 12, 2),
                'ami' => $mfi === null ? null : PovertyGuideline::homeAmiFromMfi($mfi, $size),
                'derived' => $size > self::PUBLISHED_SIZES,
            ];
        }

        // Read the extrapolation back through the same method the
        // calculator uses, so the preview can't quietly disagree with a
        // real determination.
        $preview = [];
        if ($this->year !== null && ! $this->unsavedYear) {
            foreach (self::PREVIEW_SIZES as $size) {
                $preview[$size] = PovertyGuideline::thresholdFor(
                    $size, 'annual', (int) $this->year, PovertyGuideline::REGION_HOME_ABE
                );
            }
        }

        return view('livewire.homes.settings.income-limits', [
            'years' => $years,
            'rows' => $rows,
            'preview' => array_filter($preview, fn ($v) => $v !== null),
            'medianFamilyIncome' => $mfi,
            'mfiIsDerived' => ! (is_numeric($this->mfi) && (float) $this->mfi > 0),
            'tiers' => PovertyGuideline::HOME_TIERS,
            'canEdit' => $this->can('poverty-guidelines.edit'),
            'canCreate' => $this->can('poverty-guidelines.create'),
            'canDelete' => $this->can('poverty-guidelines.delete'),
        ]);
    }
}
