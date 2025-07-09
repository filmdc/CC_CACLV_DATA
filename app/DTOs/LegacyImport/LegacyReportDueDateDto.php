<?php

namespace App\DTOs\LegacyImport;

use Carbon\Carbon;

class LegacyReportDueDateDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $due_date,
        public readonly string $status,
        public readonly ?string $description,
        public readonly int $contract_id,
        public readonly ?string $contract_title,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        public readonly ?string $deleted_at
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            due_date: $data['due_date'],
            status: $data['status'],
            description: $data['description'] ?? null,
            contract_id: (int) $data['contract_id'],
            contract_title: $data['contract_title'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            deleted_at: $data['deleted_at'] ?? null
        );
    }

    public function isValid(): bool
    {
        $errors = $this->getValidationErrors();
        return empty($errors);
    }

    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->due_date)) {
            $errors[] = 'Due date is required';
        } else {
            try {
                Carbon::parse($this->due_date);
            } catch (\Exception) {
                $errors[] = 'Invalid due date format';
            }
        }

        if (empty($this->status)) {
            $errors[] = 'Status is required';
        }

        if (!$this->contract_id) {
            $errors[] = 'Contract ID is required';
        }

        return $errors;
    }

    public function toNewSystemArray(int $newContractId, string $mappedStatus, array $defaultReminderIntervals): array
    {
        return [
            'contract_id' => $newContractId,
            'report_name' => $this->description ?: 'Imported Report',
            'due_date' => $this->parseDate($this->due_date),
            'description' => $this->description,
            'status' => $mappedStatus,
            'active' => true,
            'reminder_intervals' => $defaultReminderIntervals,
            'created_by' => 1, // System user
        ];
    }

    protected function parseDate(string $dateString): string
    {
        return Carbon::parse($dateString)->format('Y-m-d');
    }
}