<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntakeRequirementFactory extends Factory
{
    protected $model = IntakeRequirement::class;

    public function definition(): array
    {
        return [
            'communication_log_id' => CommunicationLog::factory(),
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => 'Application',
            'status' => IntakeRequirement::STATUS_OUTSTANDING,
            'received_at' => null,
            'received_by_user_id' => null,
            'source_type' => IntakeRequirement::SOURCE_BASELINE,
            'source_template_id' => null,
            'source_label_snapshot' => null,
            'is_orphaned' => false,
            'notes' => null,
        ];
    }

    public function document(): static
    {
        return $this->state(fn () => ['type' => IntakeRequirement::TYPE_DOCUMENT]);
    }

    public function task(): static
    {
        return $this->state(fn () => ['type' => IntakeRequirement::TYPE_TASK]);
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => IntakeRequirement::STATUS_RECEIVED,
            'received_at' => now(),
        ]);
    }

    public function waived(): static
    {
        return $this->state(fn () => [
            'status' => IntakeRequirement::STATUS_WAIVED,
            'received_at' => now(),
        ]);
    }

    public function orphaned(): static
    {
        return $this->state(fn () => ['is_orphaned' => true]);
    }

    public function baseline(): static
    {
        return $this->state(fn () => [
            'source_type' => IntakeRequirement::SOURCE_BASELINE,
            'source_template_id' => null,
            'source_label_snapshot' => null,
            'label' => 'Application',
        ]);
    }

    public function fundCode(?int $templateId = null, ?string $label = null): static
    {
        return $this->state(fn () => [
            'source_type' => IntakeRequirement::SOURCE_FUND_CODE,
            'source_template_id' => $templateId,
            'source_label_snapshot' => $label ?? 'Fund-code template snapshot',
            'label' => $label ?? 'Fund-code template snapshot',
        ]);
    }

    public function adHoc(): static
    {
        return $this->state(fn () => [
            'source_type' => IntakeRequirement::SOURCE_AD_HOC,
            'source_template_id' => null,
            'source_label_snapshot' => null,
        ]);
    }
}
