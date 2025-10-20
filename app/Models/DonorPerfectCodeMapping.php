<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * DonorPerfectCodeMapping Model
 *
 * Maps DonorPerfect codes to Sage Intacct codes for automated synchronization.
 * Tracks GL codes, campaigns, solicit codes, and sub-solicit codes.
 *
 * @property int $id
 * @property string $field_type Type of code (gl_code, campaign, solicit_code, sub_solicit_code)
 * @property string $dp_code Code value from DonorPerfect
 * @property string|null $dp_description Description from DonorPerfect
 * @property string|null $intacct_code Mapped Sage Intacct code
 * @property bool $is_mapped Whether this code has been mapped
 * @property bool $is_archived Whether this code is archived/inactive
 * @property \Carbon\Carbon $first_seen_at First time this code was seen in DonorPerfect
 * @property \Carbon\Carbon $last_seen_at Last time this code was seen in DonorPerfect
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class DonorPerfectCodeMapping extends Model
{
    /**
     * Field type constants
     */
    const TYPE_GL_CODE = 'gl_code';
    const TYPE_CAMPAIGN = 'campaign';
    const TYPE_SOLICIT_CODE = 'solicit_code';
    const TYPE_SUB_SOLICIT_CODE = 'sub_solicit_code';

    protected $table = 'donor_perfect_code_mappings';

    protected $fillable = [
        'field_type',
        'dp_code',
        'dp_description',
        'intacct_code',
        'is_mapped',
        'is_archived',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_mapped' => 'boolean',
        'is_archived' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Scope to filter mapped codes
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeMapped(Builder $query): Builder
    {
        return $query->where('is_mapped', true);
    }

    /**
     * Scope to filter unmapped codes
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnmapped(Builder $query): Builder
    {
        return $query->where('is_mapped', false);
    }

    /**
     * Scope to filter active codes
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to filter archived codes
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to filter by field type
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeForFieldType(Builder $query, string $type): Builder
    {
        return $query->where('field_type', $type);
    }

    /**
     * Mark this code mapping as archived
     *
     * @return bool
     */
    public function markAsArchived(): bool
    {
        return $this->update(['is_archived' => true]);
    }

    /**
     * Mark this code mapping as active
     *
     * @return bool
     */
    public function markAsActive(): bool
    {
        return $this->update(['is_archived' => false]);
    }

    /**
     * Set the Intacct code mapping
     *
     * @param string $intacctCode
     * @return bool
     */
    public function setMapping(string $intacctCode): bool
    {
        return $this->update([
            'intacct_code' => $intacctCode,
            'is_mapped' => true,
        ]);
    }

    /**
     * Override touch to update last_seen_at
     *
     * @param string|null $attribute
     * @return bool
     */
    public function touch($attribute = null): bool
    {
        if ($attribute === null) {
            $this->last_seen_at = now();
        }

        return parent::touch($attribute);
    }

    /**
     * Get the mapped Intacct code for a specific DonorPerfect code
     *
     * @param string $fieldType
     * @param string $dpCode
     * @return string|null
     */
    public static function getMappingFor(string $fieldType, string $dpCode): ?string
    {
        $mapping = self::where('field_type', $fieldType)
            ->where('dp_code', $dpCode)
            ->where('is_mapped', true)
            ->where('is_archived', false)
            ->first();

        return $mapping?->intacct_code;
    }
}
