<?php

namespace Database\Seeders;

use App\Models\RequirementDefinition;
use Illuminate\Database\Seeder;

/**
 * Comm Log v2 (Option A): default contents of the Requirements Library
 * (the "requirement items" Kaitlyn curates). Captured from the dev
 * library on 2026-05-27 so production can be repopulated.
 *
 * Idempotent: updateOrCreate keyed on `key`. Re-running refreshes the
 * label/kind/category/hint of existing items and adds any missing ones;
 * it never deletes or duplicates. Soft-deleted items are left alone —
 * if an item here was intentionally retired in an environment, restore
 * it from the Requirements Library UI rather than re-seeding.
 *
 * Phase mapping (RequirementDefinition::PHASE_*_CATEGORIES):
 *   - eligibility / compliance        → Phase 1 (intake / Comm Log)
 *   - service_delivery_compliance     → Phase 2 (housing project)
 *
 * Fund-code and housing-service links are environment-specific (they
 * reference ids that differ per database) and are NOT seeded here.
 */
class RequirementDefinitionSeeder extends Seeder
{
    private const ITEMS = [
        // ── Phase 1: intake / eligibility ──────────────────────────
        ['key' => 'application', 'label' => 'Application', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => null],
        ['key' => 'benefit-letter', 'label' => 'Benefit Letter', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => null],
        ['key' => 'income-eligibility', 'label' => 'Income Eligibility', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => 'Annual household income, household size, and proof: pay stubs, tax return, benefit letters.'],
        ['key' => 'homeowners-insurance', 'label' => 'Homeowners Insurance', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => null],
        ['key' => 'pay-stubs', 'label' => 'Pay Stubs', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => null],
        ['key' => 'photo-id', 'label' => 'Photo ID', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => "Driver's license"],
        ['key' => 'tax-returns', 'label' => 'Tax Returns', 'kind' => 'doc', 'category' => 'eligibility', 'default_required' => true, 'hint' => null],

        // ── Phase 2: housing project / service delivery ────────────
        ['key' => 'needs-signed-contract', 'label' => 'Needs signed contract', 'kind' => 'verify', 'category' => 'service_delivery_compliance', 'default_required' => true, 'hint' => null],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $item) {
            RequirementDefinition::updateOrCreate(
                ['key' => $item['key']],
                [
                    'label' => $item['label'],
                    'kind' => $item['kind'],
                    'category' => $item['category'],
                    'default_required' => $item['default_required'],
                    'hint' => $item['hint'],
                ]
            );
        }

        if (isset($this->command)) {
            $this->command->info('Seeded Requirements Library defaults ('.count(self::ITEMS).' requirement items).');
        }
    }
}
