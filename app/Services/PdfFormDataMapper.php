<?php

namespace App\Services;

use App\Models\PdfFormTemplate;
use App\Models\PdfFormFieldMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PdfFormDataMapper
{
    /**
     * Resolve all field data for a template based on mappings
     *
     * @param PdfFormTemplate $template The PDF template
     * @param Model $primaryModel The primary model (e.g., HousingJob)
     * @param array $additionalContext Additional models/data to use
     * @return array Associative array of field_name => resolved_value
     */
    public static function resolveData(
        PdfFormTemplate $template,
        Model $primaryModel,
        array $additionalContext = []
    ): array {
        $mappings = $template->fieldMappings()->mapped()->get();
        $data = [];

        foreach ($mappings as $mapping) {
            try {
                $value = self::resolveField($mapping, $primaryModel, $additionalContext);
                $data[$mapping->field_name] = $value;
            } catch (\Exception $e) {
                \Log::warning('Failed to resolve field data', [
                    'template_id' => $template->id,
                    'field_name' => $mapping->field_name,
                    'error' => $e->getMessage(),
                ]);

                // Continue with null value for this field
                $data[$mapping->field_name] = null;
            }
        }

        return $data;
    }

    /**
     * Resolve a single field mapping to its value
     *
     * @param PdfFormFieldMapping $mapping Field mapping configuration
     * @param Model $primaryModel Primary model for data resolution
     * @param array $additionalContext Additional context models
     * @return mixed Resolved value
     */
    public static function resolveField(
        PdfFormFieldMapping $mapping,
        Model $primaryModel,
        array $additionalContext = []
    ) {
        switch ($mapping->mapping_type) {
            case 'model_field':
                return self::resolveModelField($mapping, $primaryModel, $additionalContext);

            case 'custom_value':
                return $mapping->custom_value;

            case 'computed':
                return self::resolveComputedField($mapping, $primaryModel, $additionalContext);

            case 'unmapped':
            default:
                return null;
        }
    }

    /**
     * Resolve a model field value
     *
     * @param PdfFormFieldMapping $mapping Field mapping
     * @param Model $primaryModel Primary model
     * @param array $additionalContext Additional context
     * @return mixed Resolved value
     */
    protected static function resolveModelField(
        PdfFormFieldMapping $mapping,
        Model $primaryModel,
        array $additionalContext
    ) {
        // Determine which model to use
        $model = self::getTargetModel($mapping->model_name, $primaryModel, $additionalContext);

        if (!$model) {
            return null;
        }

        // Build the full path (relationship.field or just field)
        $path = $mapping->relationship_path
            ? $mapping->relationship_path . '.' . $mapping->model_field
            : $mapping->model_field;

        // Use Laravel's data_get helper for nested access
        $value = data_get($model, $path);

        // Format the value appropriately
        return self::formatValue($value, $mapping->field_type);
    }

    /**
     * Get the target model based on model name
     *
     * @param string|null $modelName Model class name
     * @param Model $primaryModel Primary model
     * @param array $additionalContext Additional models
     * @return Model|null Target model
     */
    protected static function getTargetModel(
        ?string $modelName,
        Model $primaryModel,
        array $additionalContext
    ): ?Model {
        if (!$modelName) {
            return $primaryModel;
        }

        // Check if it's the primary model
        if (class_basename($primaryModel) === $modelName) {
            return $primaryModel;
        }

        // Check if it's a relationship on the primary model
        if (method_exists($primaryModel, lcfirst($modelName))) {
            $relationMethod = lcfirst($modelName);
            return $primaryModel->$relationMethod;
        }

        // Check additional context
        if (isset($additionalContext[$modelName])) {
            return $additionalContext[$modelName];
        }

        // Try to access as a relationship
        $relationName = self::guessRelationshipName($modelName);
        if ($relationName && method_exists($primaryModel, $relationName)) {
            return $primaryModel->$relationName;
        }

        return null;
    }

    /**
     * Guess relationship name from model name
     *
     * @param string $modelName Model class name
     * @return string|null Relationship method name
     */
    protected static function guessRelationshipName(string $modelName): ?string
    {
        // Convert "Participant" → "participant"
        // Convert "HousingJob" → "housingJob"
        return lcfirst($modelName);
    }

    /**
     * Resolve a computed field value
     *
     * @param PdfFormFieldMapping $mapping Field mapping
     * @param Model $primaryModel Primary model
     * @param array $additionalContext Additional context
     * @return mixed Computed value
     */
    protected static function resolveComputedField(
        PdfFormFieldMapping $mapping,
        Model $primaryModel,
        array $additionalContext
    ) {
        $method = $mapping->computed_method;

        if (!$method) {
            return null;
        }

        // Try to call the computed method on this class
        if (method_exists(self::class, $method)) {
            return self::$method($primaryModel, $additionalContext);
        }

        // Try to call it on the primary model
        if (method_exists($primaryModel, $method)) {
            return $primaryModel->$method();
        }

        return null;
    }

    /**
     * Format a value based on field type
     *
     * @param mixed $value Raw value
     * @param string|null $fieldType Field type
     * @return mixed Formatted value
     */
    protected static function formatValue($value, ?string $fieldType)
    {
        if ($value === null) {
            return null;
        }

        switch ($fieldType) {
            case 'checkbox':
                // Convert boolean to Yes/Off
                return $value ? 'Yes' : 'Off';

            case 'text':
                // Convert to string
                return (string) $value;

            case 'dropdown':
            case 'radio':
                // Ensure string
                return (string) $value;

            default:
                return $value;
        }
    }

    // ========================================
    // Common Computed Field Methods
    // ========================================

    /**
     * Get today's date in standard format
     *
     * @return string
     */
    public static function todayDate(): string
    {
        return now()->format('m/d/Y');
    }

    /**
     * Get current user's name
     *
     * @return string|null
     */
    public static function currentUserName(): ?string
    {
        return Auth::user()?->name;
    }

    /**
     * Calculate age from date of birth
     *
     * @param Model $model Model with date_of_birth field
     * @return int|null Age in years
     */
    public static function calculateAge(Model $model): ?int
    {
        if (!isset($model->date_of_birth)) {
            return null;
        }

        $dob = $model->date_of_birth;

        if (!$dob instanceof \DateTimeInterface) {
            try {
                $dob = new \DateTime($dob);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $dob->diff(now())->y;
    }

    /**
     * Format phone number
     *
     * @param Model $model Model with phone/telephone field
     * @return string|null Formatted phone
     */
    public static function formatPhoneNumber(Model $model): ?string
    {
        $phone = $model->phone ?? $model->telephone ?? null;

        if (!$phone) {
            return null;
        }

        // Strip non-numeric
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format as (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }

        return $phone;
    }

    /**
     * Get full address as single line
     *
     * @param Model $model Model with address fields
     * @return string|null Full address
     */
    public static function fullAddress(Model $model): ?string
    {
        $parts = [];

        if ($model->address_line_1 ?? null) {
            $parts[] = $model->address_line_1;
        }

        if ($model->address_line_2 ?? null) {
            $parts[] = $model->address_line_2;
        }

        if ($model->city ?? null) {
            $parts[] = $model->city;
        }

        $stateParts = [];
        if ($model->state ?? null) {
            $stateParts[] = $model->state;
        }
        if ($model->zip_code ?? null) {
            $stateParts[] = $model->zip_code;
        }

        if (!empty($stateParts)) {
            $parts[] = implode(' ', $stateParts);
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Format currency value
     *
     * @param mixed $value Numeric value
     * @return string Formatted currency
     */
    public static function formatCurrency($value): string
    {
        if ($value === null) {
            return '$0.00';
        }

        return '$' . number_format((float) $value, 2);
    }

    /**
     * Get yes/no from boolean
     *
     * @param mixed $value Boolean value
     * @return string 'Yes' or 'No'
     */
    public static function yesNo($value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
