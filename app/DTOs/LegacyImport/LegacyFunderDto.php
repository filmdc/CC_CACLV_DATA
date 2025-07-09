<?php

namespace App\DTOs\LegacyImport;

class LegacyFunderDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $funder_name,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        public readonly ?string $deleted_at
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            funder_name: $data['funder_name'],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            deleted_at: $data['deleted_at'] ?? null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->funder_name);
    }

    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->funder_name)) {
            $errors[] = 'Funder name is required';
        }

        return $errors;
    }

    public function toNewSystemArray(string $mappedName): array
    {
        return [
            'name' => $mappedName,
            'description' => "Imported from legacy contract database",
            'active' => true,
            'created_by' => 1, // System user
        ];
    }
}