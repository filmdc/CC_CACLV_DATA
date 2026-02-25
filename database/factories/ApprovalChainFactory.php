<?php

namespace Database\Factories;

use App\Models\ApprovalChain;
use App\Models\ApprovalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalChain>
 */
class ApprovalChainFactory extends Factory
{
    protected $model = ApprovalChain::class;

    public function definition(): array
    {
        return [
            'approval_type_id' => ApprovalType::factory(),
            'step_number' => 1,
            'step_name' => fake()->words(3, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forStep(int $stepNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'step_number' => $stepNumber,
        ]);
    }
}
