<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'communication_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d H:i:s'),
            'subject' => 'Fixture Communication '.$this->faker->words(3, true),
            'communication_type' => 'phone_call',
            'direction' => 'outgoing',
            'category' => 'general',
            'priority' => 'normal',
            'contact_first_name' => $this->faker->firstName(),
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0'.$this->faker->numberBetween(100, 999),
            'summary' => 'Test summary for regression coverage.',
            'follow_up_completed' => false,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => [
            'converted_at' => '2026-03-15 09:00:00',
        ]);
    }

    public function withFollowUp(): static
    {
        return $this->state(fn () => [
            'follow_up_date' => '2026-05-01',
            'follow_up_action' => 'Call back about contractor estimate.',
        ]);
    }
}
