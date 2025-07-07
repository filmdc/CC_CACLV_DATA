<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomImportTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'source_system',
        'active',
        'field_mappings',
        'transformation_rules',
        'validation_rules',
        'import_settings',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'active' => 'boolean',
        'field_mappings' => 'array',
        'transformation_rules' => 'array',
        'validation_rules' => 'array',
        'import_settings' => 'array'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(CustomImportLog::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePreconfigured($query)
    {
        return $query->where('type', 'preconfigured');
    }

    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    public function scopeBySourceSystem($query, $sourceSystem)
    {
        return $query->where('source_system', $sourceSystem);
    }

    public function getFieldMapping($csvColumn): ?string
    {
        return $this->field_mappings[$csvColumn] ?? null;
    }

    public function getTransformationRule($field): ?array
    {
        return $this->transformation_rules[$field] ?? null;
    }

    public function getValidationRule($field): ?array
    {
        return $this->validation_rules[$field] ?? null;
    }

    public function getImportSetting($key, $default = null)
    {
        return $this->import_settings[$key] ?? $default;
    }

    public function isPreconfigured(): bool
    {
        return $this->type === 'preconfigured';
    }

    public function isCustom(): bool
    {
        return $this->type === 'custom';
    }

    public static function getPreconfiguredTemplates()
    {
        return static::active()->preconfigured()->orderBy('name')->get();
    }

    public static function getCustomTemplates()
    {
        return static::active()->custom()->orderBy('name')->get();
    }

    public static function getBySourceSystem($sourceSystem)
    {
        return static::active()->bySourceSystem($sourceSystem)->first();
    }
}