<?php

namespace Database\Factories;

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectMilestoneFactory extends Factory
{
    protected $model = ProjectMilestone::class;

    public function definition(): array
    {
        return [
            'housing_job_id' => HousingJob::factory(),
            'name' => 'Fixture Milestone '.$this->faker->words(2, true),
            'description' => 'Test milestone for regression coverage.',
            'sequence_order' => $this->faker->numberBetween(1, 10),
            'status' => 'not_started',
            'icon' => 'Circle',
            'color' => '#6366f1',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'in_progress',
            'target_date' => '2026-06-01',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'target_date' => '2026-02-01',
            'completed_date' => '2026-02-10',
        ]);
    }
}
