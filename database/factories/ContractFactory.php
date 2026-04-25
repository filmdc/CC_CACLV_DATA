<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 year', 'now');
        $end = (clone $start)->modify('+1 year');

        return [
            'title' => 'Fixture Contract ' . $this->faker->unique()->numberBetween(1000, 9999),
            'program_id' => Program::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'contract_number' => 'CT-' . $this->faker->unique()->numberBetween(10000, 99999),
            'amount' => $this->faker->randomFloat(2, 10000, 500000),
            'archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'archived' => true,
            'date_archived' => '2026-01-01 00:00:00',
        ]);
    }
}
