<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+6 months');
        $endDate = $this->faker->optional(0.7)->dateTimeBetween($startDate, '+2 years');

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'program_id' => Program::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'active' => $this->faker->boolean(85), // 85% chance of being active
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