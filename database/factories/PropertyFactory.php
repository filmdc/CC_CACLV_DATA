<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $streetNumber = $this->faker->numberBetween(1, 9999);
        $streetName = $this->faker->randomElement([
            'Test Lane', 'Fixture Avenue', 'Sample Street', 'Example Road',
            'Mock Drive', 'Dummy Court', 'Placeholder Way', 'Demo Circle',
        ]);

        return [
            'participant_id' => Participant::factory(),
            'household_id' => null,
            'address_line_1' => "{$streetNumber} {$streetName}",
            'address_line_2' => null,
            'city' => 'Allentown',
            'state' => 'PA',
            'zip_code' => '18101',
            'county' => 'Lehigh',
            // MySQL YEAR(4) accepts 1901–2155; using 1900 here flakes inserts.
            'year_built' => $this->faker->numberBetween(1901, 2020),
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->randomElement([1.0, 1.5, 2.0, 2.5, 3.0]),
            'square_footage' => $this->faker->numberBetween(600, 3500),
            'status' => 'active',
        ];
    }

    public function ownerOccupied(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
