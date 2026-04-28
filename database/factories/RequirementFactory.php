<?php

namespace Database\Factories;

use App\Models\HousingJob;
use App\Models\Requirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequirementFactory extends Factory
{
    protected $model = Requirement::class;

    public function definition(): array
    {
        return [
            'housing_job_id' => HousingJob::factory(),
            'type' => 'task',
            'title' => 'Fixture requirement '.$this->faker->words(3, true),
            'description' => null,
            'status' => Requirement::STATUS_OUTSTANDING,
            'owner_user_id' => null,
            'source_type' => Requirement::SOURCE_AD_HOC,
            'source_id' => null,
            'source_template_id' => null,
            'source_label_snapshot' => null,
        ];
    }

    public function document(): static
    {
        return $this->state(fn () => ['type' => Requirement::TYPE_DOCUMENT]);
    }

    public function task(): static
    {
        return $this->state(fn () => ['type' => Requirement::TYPE_TASK]);
    }

    public function complete(): static
    {
        return $this->state(fn () => [
            'status' => Requirement::STATUS_COMPLETE,
            'completed_at' => now(),
        ]);
    }

    public function fromMilestone(int $milestoneId): static
    {
        return $this->state(fn () => [
            'source_type' => Requirement::SOURCE_MILESTONE,
            'source_id' => $milestoneId,
            'source_label_snapshot' => 'Snapshot label',
        ]);
    }

    public function fromFundCode(int $fundCodeId, ?int $templateId = null): static
    {
        return $this->state(fn () => [
            'source_type' => Requirement::SOURCE_FUND_CODE,
            'source_id' => $fundCodeId,
            'source_template_id' => $templateId,
            'source_label_snapshot' => 'Snapshot label',
        ]);
    }
}
