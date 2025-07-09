<?php

namespace App\Rules\LegacyImport;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLegacyReportDueDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Report due date data must be an array');
            return;
        }

        // Required fields
        if (empty($value['due_date'])) {
            $fail('Report due date is required');
            return;
        }

        try {
            Carbon::parse($value['due_date']);
        } catch (\Exception) {
            $fail('Report due date must be a valid date');
            return;
        }

        if (empty($value['status'])) {
            $fail('Report status is required');
            return;
        }

        // Valid status values
        $validStatuses = ['incomplete', 'completed', 'pending', 'overdue'];
        if (!in_array(strtolower($value['status']), $validStatuses)) {
            $fail('Report status must be one of: ' . implode(', ', $validStatuses));
            return;
        }

        if (empty($value['contract_id']) && empty($value['contract_title'])) {
            $fail('Report must be associated with a contract');
            return;
        }

        // Contract ID validation
        if (isset($value['contract_id'])) {
            if (!is_numeric($value['contract_id']) || intval($value['contract_id']) <= 0) {
                $fail('Contract ID must be a positive integer');
                return;
            }
        }
    }
}