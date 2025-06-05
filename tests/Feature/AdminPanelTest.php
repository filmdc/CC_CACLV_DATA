<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_loads_successfully()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin-panel');

        $response->assertStatus(200);
    }

    public function test_program_creation_works()
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class)
            ->set('program_name', 'Test Program')
            ->set('program_description', 'Test Description')
            ->set('program_start_date', '2025-01-01')
            ->set('program_end_date', '2025-12-31')
            ->set('active', true);

        // Make sure validation passes for date fields
        $component->assertHasNoErrors(['program_start_date']);
        
        $component->call('createProgram')
            ->assertHasNoErrors()
            ->assertSet('showCreateForm', false);

        $this->assertDatabaseHas('programs', [
            'name' => 'Test Program',
            'description' => 'Test Description',
            'start_date' => '2025-01-01 00:00:00',
            'end_date' => '2025-12-31 00:00:00',
            'active' => 1,
        ]);
    }

    public function test_program_creation_validates_required_fields()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class)
            ->set('program_name', '')
            ->set('program_start_date', '')
            ->call('createProgram')
            ->assertHasErrors(['program_name', 'program_start_date']);
    }

    public function test_program_creation_validates_end_date_after_start_date()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class)
            ->set('program_name', 'Test Program')
            ->set('program_start_date', '2024-12-31')
            ->set('program_end_date', '2024-01-01')
            ->call('createProgram')
            ->assertHasErrors(['program_end_date']);
    }

    public function test_program_status_toggle_works()
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class)
            ->call('toggleProgramStatus', $program->id);

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'active' => false,
        ]);
    }

    public function test_date_field_real_time_validation()
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class)
            ->set('program_start_date', '2025-01-01');

        // Real-time validation should pass for valid date
        $component->assertHasNoErrors(['program_start_date']);

        // Test invalid date
        $component->set('program_start_date', 'invalid-date')
            ->assertHasErrors(['program_start_date']);

        // Test empty date (should error since it's required)
        $component->set('program_start_date', '')
            ->assertHasErrors(['program_start_date']);
    }

    public function test_keyboard_date_input_formats()
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\AdminPanel::class);

        // Test MM/DD/YYYY format (common keyboard input)
        $component->set('program_start_date', '01/15/2025')
            ->assertHasNoErrors(['program_start_date'])
            ->assertSet('program_start_date', '2025-01-15');

        // Test MM-DD-YYYY format
        $component->set('program_start_date', '03-20-2025')
            ->assertHasNoErrors(['program_start_date'])
            ->assertSet('program_start_date', '2025-03-20');

        // Test YYYY/MM/DD format
        $component->set('program_start_date', '2025/12/25')
            ->assertHasNoErrors(['program_start_date'])
            ->assertSet('program_start_date', '2025-12-25');

        // Test that normalized date works for program creation
        $component->set('program_name', 'Test Program')
            ->set('program_start_date', '06/15/2025')
            ->call('createProgram')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programs', [
            'name' => 'Test Program',
            'start_date' => '2025-06-15 00:00:00',
        ]);
    }
}