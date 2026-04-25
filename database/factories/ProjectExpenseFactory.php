<?php

namespace Database\Factories;

use App\Models\HousingJob;
use App\Models\ProjectExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectExpenseFactory extends Factory
{
    protected $model = ProjectExpense::class;

    public function definition(): array
    {
        return [
            'housing_job_id' => HousingJob::factory(),
            'category' => 'Roofing',
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'expense_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'description' => 'Fixture expense entry.',
            'contract_id' => null,
        ];
    }

    public function credit(): static
    {
        return $this->state(fn () => [
            'amount' => -1 * abs($this->faker->randomFloat(2, 50, 500)),
            'description' => 'Fixture credit/refund entry.',
        ]);
    }
}
