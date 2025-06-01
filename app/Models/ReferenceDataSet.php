<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReferenceDataSet extends Model
{
    protected $fillable = [
        'name',
        'description', 
        'version',
        'is_active',
        'is_draft',
        'activated_at',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_draft' => 'boolean',
        'activated_at' => 'datetime'
    ];

    public function referenceData(): HasMany
    {
        return $this->hasMany(ReferenceData::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public static function getActive(): ?self
    {
        return self::where('is_active', true)
                   ->where('is_draft', false)
                   ->orderBy('activated_at', 'desc')
                   ->first();
    }

    public function activate(): bool
    {
        if ($this->is_draft) {
            return false;
        }

        // Deactivate all other sets
        self::where('is_active', true)->update(['is_active' => false]);
        
        // Activate this set
        $this->update([
            'is_active' => true,
            'activated_at' => now()
        ]);

        return true;
    }

    public function publish(): bool
    {
        if (!$this->is_draft) {
            return false;
        }

        $this->update(['is_draft' => false]);
        return true;
    }

    public function duplicate(string $newVersion, string $newName = null): self
    {
        $duplicate = self::create([
            'name' => $newName ?? ($this->name . ' (Copy)'),
            'description' => $this->description,
            'version' => $newVersion,
            'is_active' => false,
            'is_draft' => true,
            'created_by' => auth()->id()
        ]);

        // Copy all reference data
        foreach ($this->referenceData as $refData) {
            $duplicate->referenceData()->create($refData->only([
                'category',
                'value', 
                'label',
                'sort_order',
                'active'
            ]));
        }

        return $duplicate;
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_active) {
            return 'Active';
        } elseif ($this->is_draft) {
            return 'Draft';
        } else {
            return 'Published';
        }
    }

    public function getDataCount(): int
    {
        return $this->referenceData()->count();
    }

    public function getCategoriesCount(): int
    {
        return $this->referenceData()->distinct('category')->count('category');
    }
}
