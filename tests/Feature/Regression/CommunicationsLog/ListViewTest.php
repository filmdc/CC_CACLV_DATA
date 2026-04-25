<?php

use App\Livewire\CommunicationsLogManagement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('lists every seeded communication log on first page (5 total)', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class);

    foreach (['CL-1', 'CL-2', 'CL-3', 'CL-4', 'CL-5'] as $label) {
        $component->assertSee($label);
    }
})->group('regression');

it('search filter narrows the rendered list to CL-3 only', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'Multiple fund codes');

    $component->assertSee('CL-3')
        ->assertDontSee('CL-1: General inquiry')
        ->assertDontSee('CL-2: Single fund code')
        ->assertDontSee('CL-4: Converted')
        ->assertDontSee('CL-5: Converted');
})->group('regression');

it('category filter narrows to only intake-categorised entries', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('categoryFilter', 'intake')
        ->assertSee('CL-2')
        ->assertSee('CL-3')
        ->assertSee('CL-4')
        ->assertSee('CL-5')
        ->assertDontSee('CL-1: General inquiry');
})->group('regression');
