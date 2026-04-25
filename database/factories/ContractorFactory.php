<?php

namespace Database\Factories;

use App\Models\Contractor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractorFactory extends Factory
{
    protected $model = Contractor::class;

    public function definition(): array
    {
        $companies = [
            'Fixture Construction LLC',
            'Test Contractors Inc',
            'Sample Builders Co',
            'Mock Roofing Services',
            'Demo Plumbing & Heating',
            'Placeholder Electric',
        ];

        return [
            'company_name' => $this->faker->unique()->randomElement($companies)
                .' #'.$this->faker->unique()->numberBetween(1, 999),
            'contact_first_name' => $this->faker->firstName(),
            'contact_last_name' => 'Fixture',
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '555-555-0'.$this->faker->numberBetween(100, 999),
            'address_line_1' => $this->faker->numberBetween(1, 999).' Contractor Way',
            'city' => 'Allentown',
            'state' => 'PA',
            'zip_code' => '18101',
            'license_number' => 'PA-'.$this->faker->unique()->numberBetween(10000, 99999),
            'license_expiration' => $this->faker->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'insurance_provider' => 'Fixture Insurance Co',
            'insurance_policy_number' => 'POL-'.$this->faker->unique()->numberBetween(100000, 999999),
            'insurance_expiration' => $this->faker->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
            'liability_coverage_amount' => $this->faker->randomElement([500000, 1000000, 2000000]),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
