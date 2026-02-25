<?php

namespace Database\Factories;

use App\Models\ApprovalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalType>
 */
class ApprovalTypeFactory extends Factory
{
    protected $model = ApprovalType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'display_name' => fake()->words(3, true),
            'category' => 'General',
            'module' => 'general',
            'requires_document' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function requiresDocument(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_document' => true,
        ]);
    }
}
