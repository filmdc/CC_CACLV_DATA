<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceData extends Model
{
    protected $fillable = [
        'reference_data_set_id',
        'category',
        'value',
        'label',
        'description',
        'sort_order',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    public function referenceDataSet(): BelongsTo
    {
        return $this->belongsTo(ReferenceDataSet::class);
    }

    public static function getOptions($category, $activeOnly = true, $dataSetId = null)
    {
        $dataSetId = $dataSetId ?? self::getActiveDataSetId();
        
        if (!$dataSetId) {
            return collect();
        }

        $query = static::where('reference_data_set_id', $dataSetId)
                       ->byCategory($category)
                       ->ordered();
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->get();
    }

    protected static function getActiveDataSetId(): ?int
    {
        $activeSet = ReferenceDataSet::getActive();
        return $activeSet?->id;
    }

    public static function getOptionsForParticipant(string $category, Participant $participant)
    {
        if ($participant->reference_data_set_id) {
            return self::getOptions($category, true, $participant->reference_data_set_id);
        }
        
        return self::getOptions($category);
    }

    public static function getCategories()
    {
        return [
            'gender' => 'Gender',
            'education_level' => 'Education Level',
            'ethnicity' => 'Ethnicity',
            'race' => 'Race',
            'income_sources' => 'Income Sources',
            'health_insurance_sources' => 'Health Insurance Sources',
            'other_income_sources' => 'Other Income Sources',
            'non_cash_benefits' => 'Non-Cash Benefits',
            'housing_status' => 'Housing Status',
            'housing_type' => 'Housing Type',
            'household_type' => 'Household Type',
            'disability_status' => 'Disability Status',
            'military_status' => 'Military Status',
            'work_status' => 'Work Status',
            'health_insurance_status' => 'Health Insurance Status',
            'property_type' => 'Property Type',
            'housing_job_type' => 'Housing Job Type',
            'contractor_service' => 'Contractor Service',
            'contact_type' => 'Contact Type'
        ];
    }
}
