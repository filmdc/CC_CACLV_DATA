<?php

namespace Database\Factories;

use App\Models\FundCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundCodeFactory extends Factory
{
    protected $model = FundCode::class;

    public function definition(): array
    {
        return [
            'code' => 'FC-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => 'Fixture Fund ' . $this->faker->words(2, true),
            'contract_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
