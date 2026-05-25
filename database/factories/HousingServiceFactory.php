<?php

namespace Database\Factories;

use App\Models\HousingService;
use Illuminate\Database\Eloquent\Factories\Factory;

class HousingServiceFactory extends Factory
{
    protected $model = HousingService::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'First-Time Homebuyer Counseling',
                'PHFA HEMAP Application Assistance',
                'Rental Housing Counseling',
                'Foreclosure Prevention',
                'Pre-Purchase Education',
                'Weatherization Referral',
                'Tenant Rights Education',
            ]).' ('.$this->faker->numberBetween(100, 999).')',
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
