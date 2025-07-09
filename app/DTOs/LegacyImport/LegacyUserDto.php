<?php

namespace App\DTOs\LegacyImport;

class LegacyUserDto
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $email_verified_at,
        public readonly ?bool $approved,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        public readonly ?string $deleted_at
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            email_verified_at: $data['email_verified_at'] ?? null,
            approved: isset($data['approved']) ? (bool) $data['approved'] : null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            deleted_at: $data['deleted_at'] ?? null
        );
    }

    public function isValid(): bool
    {
        return !empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email format is invalid';
        }

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }

        return $errors;
    }

    public function toNewSystemArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->approved ?? true,
            'email_verified_at' => $this->approved ? now() : null,
            'password' => null,
        ];
    }
}