<?php

namespace Database\Factories;

use App\Models\PendingApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PendingApproval>
 */
class PendingApprovalFactory extends Factory
{
    protected $model = PendingApproval::class;

    public function definition(): array
    {
        return [
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => User::factory(),
            'item_type' => 'general',
            'item_name' => fake()->sentence(),
            'status' => 'pending',
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
            'actioned_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'actioned_at' => now(),
        ]);
    }

    public function withPriority(int $priority = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}
