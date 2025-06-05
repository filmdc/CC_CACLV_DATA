<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_management_loads_successfully()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/service-management');

        $response->assertStatus(200);
    }

    public function test_service_creation_works()
    {
        $user = User::factory()->create();
        $program = Program::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\ServiceManagement::class)
            ->set('name', 'Test Service')
            ->set('description', 'Test Description')
            ->set('program_id', $program->id)
            ->set('start_date', '2025-01-01')
            ->set('end_date', '2025-12-31')
            ->set('active', true)
            ->call('createService')
            ->assertHasNoErrors()
            ->assertSet('showCreateForm', false);

        $this->assertDatabaseHas('services', [
            'name' => 'Test Service',
            'description' => 'Test Description',
            'program_id' => $program->id,
            'start_date' => '2025-01-01 00:00:00',
            'end_date' => '2025-12-31 00:00:00',
            'active' => 1,
        ]);
    }

    public function test_service_update_works()
    {
        $user = User::factory()->create();
        $program = Program::factory()->create();
        $service = Service::factory()->create(['program_id' => $program->id]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ServiceManagement::class)
            ->call('showEditService', $service->id)
            ->set('name', 'Updated Service Name')
            ->call('updateService')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Service Name',
        ]);
    }

    public function test_service_status_toggle_works()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ServiceManagement::class)
            ->call('toggleServiceStatus', $service->id);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'active' => false,
        ]);
    }

    public function test_service_validation_works()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\ServiceManagement::class)
            ->set('name', '')
            ->set('program_id', '')
            ->set('start_date', '')
            ->call('createService')
            ->assertHasErrors(['name', 'program_id', 'start_date']);
    }
}