<?php

namespace Database\Factories;

use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class HousingJobFactory extends Factory
{
    protected $model = HousingJob::class;

    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'property_id' => Property::factory(),
            'program_id' => Program::factory(),
            'household_id' => null,
            'job_number' => 'JOB-FIX-'.$this->faker->unique()->numberBetween(10000, 99999),
            'job_title' => 'Fixture Job '.$this->faker->words(2, true),
            'job_description' => 'Test job description for regression coverage.',
            'request_date' => '2026-01-15',
            'status' => 'pending',
            'priority' => 'medium',
            'estimated_cost' => $this->faker->randomFloat(2, 5000, 75000),
            'is_template' => false,
        ];
    }

    public function template(): static
    {
        return $this->state(fn () => [
            'is_template' => true,
            'template_name' => 'Fixture Template '.$this->faker->unique()->numberBetween(1, 999),
            'template_description' => 'Reusable template for regression tests.',
            'times_used' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'in_progress',
            'start_date' => '2026-02-01',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'start_date' => '2025-11-01',
            'actual_completion_date' => '2026-02-15',
        ]);
    }
}
