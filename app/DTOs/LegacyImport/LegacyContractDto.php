<?php

namespace App\DTOs\LegacyImport;

use Carbon\Carbon;

class LegacyContractDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $contract_number,
        public readonly ?string $cfda_number,
        public readonly ?string $purpose,
        public readonly ?int $funder_id,
        public readonly ?string $funder_name,
        public readonly ?string $date_of_award,
        public readonly ?float $amount,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $grant_code,
        public readonly ?string $notes,
        public readonly ?bool $archived,
        public readonly ?string $date_archived,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        public readonly ?string $deleted_at
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            title: $data['title'],
            contract_number: $data['contract_number'] ?? null,
            cfda_number: $data['cfda_number'] ?? null,
            purpose: $data['purpose'] ?? null,
            funder_id: isset($data['funder_id']) ? (int) $data['funder_id'] : null,
            funder_name: $data['funder_name'] ?? null,
            date_of_award: $data['date_of_award'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            grant_code: $data['grant_code'] ?? null,
            notes: $data['notes'] ?? null,
            archived: isset($data['archived']) ? (bool) $data['archived'] : false,
            date_archived: $data['date_archived'] ?? null,
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

        if (empty($this->title)) {
            $errors[] = 'Title is required';
        }

        if ($this->amount !== null && $this->amount < 0) {
            $errors[] = 'Amount cannot be negative';
        }

        if ($this->start_date && $this->end_date) {
            try {
                $startDate = Carbon::parse($this->start_date);
                $endDate = Carbon::parse($this->end_date);
                
                if ($endDate->lessThan($startDate)) {
                    $errors[] = 'End date cannot be before start date';
                }
            } catch (\Exception $e) {
                $errors[] = 'Invalid date format';
            }
        }

        return $errors;
    }

    public function toNewSystemArray(int $programId, int $funderId): array
    {
        return [
            'title' => $this->title,
            'contract_number' => $this->contract_number,
            'cfda_number' => $this->cfda_number,
            'purpose' => $this->purpose,
            'program_id' => $programId,
            'funder_id' => $funderId,
            'date_of_award' => $this->parseDate($this->date_of_award),
            'amount' => $this->amount,
            'start_date' => $this->parseDate($this->start_date),
            'end_date' => $this->parseDate($this->end_date),
            'grant_code' => $this->grant_code,
            'notes' => $this->notes,
            'archived' => $this->archived ?? false,
            'date_archived' => $this->archived ? $this->parseDate($this->date_archived) : null,
            'created_by' => 1, // System user
        ];
    }

    protected function parseDate(?string $dateString): ?string
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}