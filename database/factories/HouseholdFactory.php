<?php

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'household_type' => $this->faker->randomElement([
                'Single Person',
                'Two Adults NO Children',
                'Single Parent Female',
                'Single Parent Male',
                'Two Parent Household',
                'Non-related Adults with Children',
                'Multigenerational Household',
                'Other',
                'Unknown/not reported',
            ]),
            'household_size' => $this->faker->numberBetween(1, 6),
            'housing_status' => $this->faker->randomElement([
                'Own',
                'Rent',
                'Other permanent housing',
                'Homeless',
                'Other',
                'Unknown/not reported',
            ]),
            'monthly_income' => $this->faker->randomFloat(2, 0, 6000),
            'annual_income' => $this->faker->randomFloat(2, 0, 72000),
            'hhs_poverty_percentage' => $this->faker->randomFloat(2, 0, 300),
        ];
    }

    public function singlePerson(): static
    {
        return $this->state(fn () => [
            'household_type' => 'Single Person',
            'household_size' => 1,
        ]);
    }

    public function lowIncome(): static
    {
        return $this->state(fn () => [
            'monthly_income' => 1200,
            'annual_income' => 14400,
            'hhs_poverty_percentage' => 95,
        ]);
    }
}
