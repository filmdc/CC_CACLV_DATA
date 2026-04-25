<?php

namespace Database\Factories;

use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectBudgetFactory extends Factory
{
    protected $model = ProjectBudget::class;

    public function definition(): array
    {
        return [
            'housing_job_id' => HousingJob::factory(),
            'category' => $this->faker->unique()->randomElement([
                'Roofing', 'Plumbing', 'Electrical', 'HVAC',
                'Insulation', 'Windows', 'Flooring', 'Painting',
                'Foundation', 'Siding', 'Landscaping', 'Permits',
            ]),
            'contract_id' => null,
            'budgeted_amount' => $this->faker->randomFloat(2, 1000, 25000),
            'spent_amount' => 0,
        ];
    }

    public function withContract(): static
    {
        return $this->state(fn () => [
            'contract_id' => \App\Models\Contract::factory(),
        ]);
    }
}
