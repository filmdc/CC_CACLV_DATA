<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => 'Fixture',
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'is_head_of_household' => false,
            'gender' => $this->faker->randomElement(['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say']),
            'telephone' => '555-555-0'.$this->faker->numberBetween(100, 999),
            'email' => null,
            'employment_income' => $this->faker->randomFloat(2, 0, 60000),
        ];
    }

    public function headOfHousehold(): static
    {
        return $this->state(fn () => [
            'is_head_of_household' => true,
            'relationship_to_head' => 'self',
        ]);
    }
}
