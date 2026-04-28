<?php

namespace Database\Factories;

use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundCodeRequirementFactory extends Factory
{
    protected $model = FundCodeRequirement::class;

    public function definition(): array
    {
        return [
            'fund_code_id' => FundCode::factory(),
            'label' => 'Fixture requirement '.$this->faker->words(3, true),
            'description' => null,
            'type' => 'task',
            'is_hard_requirement' => false,
            'sort_order' => 0,
        ];
    }

    public function hard(): static
    {
        return $this->state(fn () => ['is_hard_requirement' => true]);
    }

    public function document(): static
    {
        return $this->state(fn () => ['type' => 'document']);
    }

    public function withDescription(): static
    {
        return $this->state(fn () => [
            'description' => 'Fixture description: '.$this->faker->sentence(),
        ]);
    }
}
