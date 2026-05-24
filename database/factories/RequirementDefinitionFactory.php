<?php

namespace Database\Factories;

use App\Models\RequirementDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RequirementDefinitionFactory extends Factory
{
    protected $model = RequirementDefinition::class;

    public function definition(): array
    {
        $label = $this->faker->randomElement([
            'Photo ID',
            'Proof of income (last 2 years)',
            'Federal tax returns',
            'Credit report consent',
            'Household size & member ages',
            'Mortgage delinquency notice',
            'Hardship statement',
        ]);

        return [
            'key' => Str::slug($label).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'label' => $label,
            'kind' => $this->faker->randomElement(RequirementDefinition::KINDS),
            'default_required' => true,
            'hint' => null,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn () => ['default_required' => false]);
    }

    public function kind(string $kind): static
    {
        return $this->state(fn () => ['kind' => $kind]);
    }
}
