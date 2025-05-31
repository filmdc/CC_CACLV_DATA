<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 year');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'active' => $this->faker->boolean(80), // 80% chance of being active
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}