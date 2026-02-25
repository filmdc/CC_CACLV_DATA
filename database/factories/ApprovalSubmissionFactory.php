<?php

namespace Database\Factories;

use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalSubmission>
 */
class ApprovalSubmissionFactory extends Factory
{
    protected $model = ApprovalSubmission::class;

    public function definition(): array
    {
        return [
            'approval_type_id' => ApprovalType::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'submitted_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
