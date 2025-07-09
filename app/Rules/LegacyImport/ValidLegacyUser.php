<?php

namespace App\Rules\LegacyImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLegacyUser implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('User data must be an array');
            return;
        }

        // Required fields
        if (empty($value['email'])) {
            $fail('User email is required');
            return;
        }

        if (!filter_var($value['email'], FILTER_VALIDATE_EMAIL)) {
            $fail('User email must be valid');
            return;
        }

        if (empty($value['name'])) {
            $fail('User name is required');
            return;
        }

        // Optional but validated fields
        if (isset($value['email_verified_at']) && $value['email_verified_at'] !== null) {
            if (!strtotime($value['email_verified_at'])) {
                $fail('User email_verified_at must be a valid date');
                return;
            }
        }

        if (isset($value['approved'])) {
            if (!is_bool($value['approved']) && !in_array($value['approved'], [0, 1, '0', '1'])) {
                $fail('User approved field must be boolean');
                return;
            }
        }
    }
}