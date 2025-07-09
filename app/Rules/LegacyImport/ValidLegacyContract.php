<?php

namespace App\Rules\LegacyImport;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLegacyContract implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Contract data must be an array');
            return;
        }

        // Required fields
        if (empty($value['title'])) {
            $fail('Contract title is required');
            return;
        }

        if (empty($value['funder_name']) && empty($value['funder_id'])) {
            $fail('Contract must have a funder');
            return;
        }

        // Amount validation
        if (isset($value['amount'])) {
            $amount = floatval($value['amount']);
            if ($amount < 0) {
                $fail('Contract amount cannot be negative');
                return;
            }
        }

        // Date validation
        if (isset($value['start_date']) && !empty($value['start_date'])) {
            try {
                Carbon::parse($value['start_date']);
            } catch (\Exception) {
                $fail('Contract start_date must be a valid date');
                return;
            }
        }

        if (isset($value['end_date']) && !empty($value['end_date'])) {
            try {
                Carbon::parse($value['end_date']);
            } catch (\Exception) {
                $fail('Contract end_date must be a valid date');
                return;
            }
        }

        if (isset($value['date_of_award']) && !empty($value['date_of_award'])) {
            try {
                Carbon::parse($value['date_of_award']);
            } catch (\Exception) {
                $fail('Contract date_of_award must be a valid date');
                return;
            }
        }

        // Date logic validation
        if (!empty($value['start_date']) && !empty($value['end_date'])) {
            try {
                $startDate = Carbon::parse($value['start_date']);
                $endDate = Carbon::parse($value['end_date']);
                
                if ($endDate->lessThan($startDate)) {
                    $fail('Contract end_date cannot be before start_date');
                    return;
                }
            } catch (\Exception) {
                // Already handled above
            }
        }

        // Archived validation
        if (isset($value['archived'])) {
            if (!is_bool($value['archived']) && !in_array($value['archived'], [0, 1, '0', '1'])) {
                $fail('Contract archived field must be boolean');
                return;
            }
        }
    }
}