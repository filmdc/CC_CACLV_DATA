<?php

namespace App\Rules\LegacyImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLegacyFunder implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Funder data must be an array');
            return;
        }

        // Required fields
        if (empty($value['funder_name'])) {
            $fail('Funder name is required');
            return;
        }

        // Name length validation
        if (strlen($value['funder_name']) > 255) {
            $fail('Funder name cannot exceed 255 characters');
            return;
        }

        // ID validation if present
        if (isset($value['id'])) {
            if (!is_numeric($value['id']) || intval($value['id']) <= 0) {
                $fail('Funder ID must be a positive integer');
                return;
            }
        }
    }
}