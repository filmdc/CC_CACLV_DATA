<?php

/*
| Tripwires for the Comm Log v2 (Option A) unified-requirements schema.
| Verifies that the new tables exist with the expected columns, that the
| intake_requirements enum was widened to the 6-state lifecycle, and
| that the relations on the new models wire up correctly.
*/

use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use App\Models\Service;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // No seeder required — pure schema + relation checks.
});

it('requirement_definitions has the expected columns', function () {
    expect(Schema::hasTable('requirement_definitions'))->toBeTrue();
    expect(Schema::hasColumns('requirement_definitions', [
        'id', 'key', 'label', 'kind', 'default_required', 'hint',
        'created_by_user_id', 'updated_by_user_id',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
})->group('regression');

it('requirement_template_sets has the expected columns', function () {
    expect(Schema::hasTable('requirement_template_sets'))->toBeTrue();
    expect(Schema::hasColumns('requirement_template_sets', [
        'id', 'name', 'description',
        'created_by_user_id', 'updated_by_user_id',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
})->group('regression');

it('requirement_template_set_items joins bundles to atoms with ordering and required override', function () {
    expect(Schema::hasTable('requirement_template_set_items'))->toBeTrue();
    expect(Schema::hasColumns('requirement_template_set_items', [
        'template_set_id', 'requirement_definition_id',
        'required_override', 'position',
    ]))->toBeTrue();
})->group('regression');

it('program_service_requirement_template_sets is a many-to-many service ↔ bundle pivot', function () {
    expect(Schema::hasTable('program_service_requirement_template_sets'))->toBeTrue();
    expect(Schema::hasColumns('program_service_requirement_template_sets', [
        'service_id', 'template_set_id', 'created_by_user_id', 'created_at',
    ]))->toBeTrue();
})->group('regression');

it('intake_requirements has the new key/kind/via/status_changed_* and source_definition_id columns', function () {
    expect(Schema::hasColumns('intake_requirements', [
        'key', 'kind', 'via',
        'status_changed_at', 'status_changed_by_user_id',
        'source_definition_id',
    ]))->toBeTrue();
})->group('regression');

it('intake_requirements.status accepts all 6 new lifecycle states and rejects the legacy outstanding', function () {
    $log = CommunicationLog::factory()->create();

    foreach (['pending', 'requested', 'received', 'waived', 'na', 'blocked'] as $status) {
        $row = IntakeRequirement::create([
            'communication_log_id' => $log->id,
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => 'Test '.$status,
            'status' => $status,
            'source_type' => IntakeRequirement::SOURCE_AD_HOC,
        ]);

        expect($row->fresh()->status)->toBe($status);
    }

    // legacy 'outstanding' is no longer in the enum (data was backfilled to 'pending')
    expect(fn () => IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Legacy',
        'status' => 'outstanding',
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
})->group('regression');

it('intake_requirements.source_type accepts template_set and definition', function () {
    $log = CommunicationLog::factory()->create();

    foreach (['baseline', 'fund_code', 'ad_hoc', 'template_set', 'definition'] as $source) {
        $row = IntakeRequirement::create([
            'communication_log_id' => $log->id,
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => 'Test '.$source,
            'source_type' => $source,
        ]);
        expect($row->fresh()->source_type)->toBe($source);
    }
})->group('regression');

it('RequirementTemplateSet ↔ RequirementDefinition many-to-many works with pivot data', function () {
    $atom = RequirementDefinition::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();

    $bundle->items()->attach($atom->id, [
        'required_override' => false,
        'position' => 3,
    ]);

    $loaded = $bundle->fresh()->items->first();
    expect($loaded->id)->toBe($atom->id);
    expect((bool) $loaded->pivot->required_override)->toBeFalse();
    expect((int) $loaded->pivot->position)->toBe(3);
})->group('regression');

it('RequirementTemplateSet ↔ Service many-to-many works', function () {
    // Use an existing service to avoid Service factory plumbing assumptions.
    $service = Service::query()->first() ?? Service::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();

    $bundle->services()->attach($service->id, [
        'created_at' => now(),
    ]);

    expect($bundle->fresh()->services->pluck('id')->all())->toContain($service->id);
})->group('regression');

it('IntakeRequirement::isMet() respects the 3 met statuses', function () {
    $log = CommunicationLog::factory()->create();

    foreach (['pending', 'requested', 'blocked'] as $unmet) {
        $req = IntakeRequirement::create([
            'communication_log_id' => $log->id,
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => $unmet,
            'status' => $unmet,
            'source_type' => IntakeRequirement::SOURCE_AD_HOC,
        ]);
        expect($req->isMet())->toBeFalse();
    }

    foreach (['received', 'waived', 'na'] as $met) {
        $req = IntakeRequirement::create([
            'communication_log_id' => $log->id,
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => $met,
            'status' => $met,
            'source_type' => IntakeRequirement::SOURCE_AD_HOC,
        ]);
        expect($req->isMet())->toBeTrue();
    }
})->group('regression');

it('housing_jobs has the requirement-override audit columns', function () {
    expect(Schema::hasColumns('housing_jobs', [
        'requirement_override_reason',
        'requirement_override_by_user_id',
        'requirement_overridden_at',
    ]))->toBeTrue();
})->group('regression');

it('RequirementDefinition soft-delete preserves historical IntakeRequirement.source_definition_id', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => $atom->label,
        'key' => $atom->key,
        'kind' => $atom->kind,
        'source_type' => IntakeRequirement::SOURCE_DEFINITION,
        'source_definition_id' => $atom->id,
    ]);

    $atom->delete();

    // Historical instance still points at the soft-deleted atom.
    $fresh = $req->fresh();
    expect($fresh->source_definition_id)->toBe($atom->id);

    // The atom is retrievable via the relation when withTrashed.
    expect($fresh->definition()->withTrashed()->first()?->trashed())->toBeTrue();
})->group('regression');
