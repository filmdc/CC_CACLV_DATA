<?php

namespace Database\Factories;

use App\Models\RequirementTemplateSet;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequirementTemplateSetFactory extends Factory
{
    protected $model = RequirementTemplateSet::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'HCP First-Time Homebuyer',
                'PHFA HEMAP',
                'CAH Insulation',
                'Sixth Street Shelter',
                'Rental Housing Counseling',
            ]).' '.$this->faker->numberBetween(100, 999),
            'description' => $this->faker->sentence(),
        ];
    }
}
