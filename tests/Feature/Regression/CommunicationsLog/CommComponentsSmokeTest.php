<?php

/*
| Comm Log v2 scaffolding smoke test: every x-comm.* Blade component
| renders without errors when given realistic mock props.
|
| Not a behavior test — just a tripwire so a typo in any of the 14
| component partials trips before it lands in a real form/viewer.
*/

use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Blade;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->actingAs($this->actor);
});

it('x-comm.modal-chrome renders', function () {
    $html = Blade::render('<x-comm.modal-chrome title="Edit" id="42" />');
    expect($html)->toContain('Edit')->toContain('#42');
})->group('regression');

it('x-comm.project-banner renders with no project', function () {
    $html = Blade::render('<x-comm.project-banner :project="null" :sticky="false" />');
    expect($html)->toContain('No project attached');
})->group('regression');

it('x-comm.meta-chip renders all tones', function () {
    foreach (['neutral', 'slate', 'teal', 'red', 'sage', 'amber', 'sand'] as $tone) {
        $html = Blade::render('<x-comm.meta-chip :tone="$tone">Hello</x-comm.meta-chip>', ['tone' => $tone]);
        expect($html)->toContain('Hello');
    }
})->group('regression');

it('x-comm.priority-badge renders all 4 levels', function () {
    foreach (['urgent', 'high', 'normal', 'low'] as $level) {
        $html = Blade::render('<x-comm.priority-badge :level="$level" />', ['level' => $level]);
        expect($html)->toContain(ucfirst($level));
    }
})->group('regression');

it('x-comm.fund-chip renders all expiry states', function () {
    foreach (['soon', 'warning', 'ok', 'perpetual', 'expired'] as $state) {
        $fund = (object) [
            'id' => 1,
            'code' => 'TEST',
            'name' => 'Test fund',
            'expiry_state' => $state,
            'days_to_expiry' => $state === 'soon' ? 15 : ($state === 'warning' ? 60 : ($state === 'expired' ? 7 : null)),
            'expires_at' => $state === 'perpetual' ? null : \Illuminate\Support\Carbon::now()->addDays(180),
        ];
        $html = Blade::render('<x-comm.fund-chip :fund="$fund" />', ['fund' => $fund]);
        expect($html)->toContain('TEST');
    }
})->group('regression');

it('x-comm.service-chip renders for each program code tone', function () {
    foreach (['CAH', 'HCP', 'SSS', 'OTHER'] as $code) {
        $svc = (object) ['id' => 1, 'name' => 'Test svc', 'program_code' => $code, 'program' => null];
        $html = Blade::render('<x-comm.service-chip :service="$svc" />', ['svc' => $svc]);
        expect($html)->toContain('Test svc');
    }
})->group('regression');

it('x-comm.update-item renders with each known type', function () {
    $log = CommunicationLog::factory()->create();

    // Only types currently in the communication_log_updates.update_type
    // enum — `action` / `email` arrive in the Phase 6 viewer rework.
    foreach (['note', 'follow_up', 'status_change', 'outcome', 'other'] as $type) {
        $update = $log->updates()->create([
            'update_content' => 'Test '.$type,
            'update_type' => $type,
            'created_by' => $this->actor->id,
        ]);
        $update->update_text = $update->update_content; // legacy alias used by partial
        $html = Blade::render('<x-comm.update-item :update="$update" />', ['update' => $update]);
        expect($html)->toContain('Test '.$type);
    }
})->group('regression');

it('x-comm.followup-composer renders with default models', function () {
    $html = Blade::render('<x-comm.followup-composer />');
    expect($html)->toContain('Submit');
})->group('regression');

it('x-comm.kbd renders single + chord keys', function () {
    expect(Blade::render('<x-comm.kbd>P</x-comm.kbd>'))->toContain('P');
    expect(Blade::render('<x-comm.kbd :keys="[\'⌘\', \'⏎\']" />'))->toContain('⌘');
})->group('regression');

it('x-comm.lucide renders for the curated icon set', function () {
    foreach (['search', 'x', 'check', 'pencil', 'plus', 'trash', 'paperclip', 'calendar', 'alert-triangle', 'tag', 'link'] as $name) {
        $html = Blade::render('<x-lucide :name="$name" />', ['name' => $name]);
        expect($html)->toContain('<svg');
    }
})->group('regression');

it('x-comm.tag-input renders with existing + selected tags', function () {
    $html = Blade::render('<x-comm.tag-input :existingTags="$ex" :selectedTags="$sel" />', [
        'ex' => ['urgent', 'partner-call'],
        'sel' => ['urgent'],
    ]);
    expect($html)->toContain('Add tags');
})->group('regression');

it('x-comm.project-picker renders the General intake row', function () {
    $html = Blade::render('<x-comm.project-picker :projects="[]" />');
    expect($html)->toContain('General intake');
})->group('regression');

it('x-comm.requirements-summary-pill renders ratio and status', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    $met = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Met item',
        'status' => IntakeRequirement::STATUS_RECEIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
    $pending = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Pending item',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $items = collect([$met, $pending]);
    $html = Blade::render('<x-comm.requirements-summary-pill :items="$items" />', ['items' => $items]);

    expect($html)->toContain('Requirements met');
    expect($html)->toContain('1');     // met count
    expect($html)->toContain('unmet');
})->group('regression');

it('x-comm.requirements-panel renders kind icons + status chips + via tags', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['kind' => 'doc']);

    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'photo-id',
        'kind' => 'doc',
        'via' => ['service:1', 'definition:'.$atom->id],
        'status' => IntakeRequirement::STATUS_REQUESTED,
        'source_type' => IntakeRequirement::SOURCE_DEFINITION,
        'source_definition_id' => $atom->id,
    ]);

    $items = collect([$req]);
    $html = Blade::render('<x-comm.requirements-panel :items="$items" />', ['items' => $items]);

    expect($html)->toContain('Photo ID');
    expect($html)->toContain('Requested');
    expect($html)->toContain('service:1');
})->group('regression');

it('x-comm.requirements-gate renders the OK state when everything is met', function () {
    $log = CommunicationLog::factory()->create();

    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'photo-id',
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_RECEIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $items = collect([$req]);
    $html = Blade::render('<x-comm.requirements-gate :items="$items" />', ['items' => $items]);

    expect($html)->toContain('All required requirements met');
    expect($html)->toContain('1/1');
})->group('regression');

it('x-comm.requirements-gate renders the unmet state with override block', function () {
    $log = CommunicationLog::factory()->create();

    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Hardship statement',
        'key' => 'hardship',
        'kind' => 'form',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'photo-id',
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_RECEIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $items = $log->intakeRequirements;
    $html = Blade::render('<x-comm.requirements-gate :items="$items" />', ['items' => $items]);

    expect($html)->toContain('1 required requirement');
    expect($html)->toContain('Hardship statement');
    expect($html)->toContain('Override and create anyway');
})->group('regression');
