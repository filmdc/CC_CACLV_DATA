<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // Relationships to taggable models

    public function communicationLogs(): MorphToMany
    {
        return $this->morphedByMany(CommunicationLog::class, 'taggable');
    }

    public function participants(): MorphToMany
    {
        return $this->morphedByMany(Participant::class, 'taggable');
    }

    public function households(): MorphToMany
    {
        return $this->morphedByMany(Household::class, 'taggable');
    }

    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'taggable');
    }

    public function contractors(): MorphToMany
    {
        return $this->morphedByMany(Contractor::class, 'taggable');
    }

    public function housingJobs(): MorphToMany
    {
        return $this->morphedByMany(HousingJob::class, 'taggable');
    }

    public function programs(): MorphToMany
    {
        return $this->morphedByMany(Program::class, 'taggable');
    }

    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'taggable');
    }

    public function contracts(): MorphToMany
    {
        return $this->morphedByMany(Contract::class, 'taggable');
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    // Static helper to find or create tag by name

    public static function findOrCreateByName(string $name, ?int $createdBy = null): self
    {
        $slug = Str::slug($name);

        return self::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'created_by' => $createdBy ?? auth()->id(),
            ]
        );
    }

    // Get contrasting text color for the tag background

    public function getTextColorAttribute(): string
    {
        $hex = ltrim($this->color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    // Available preset colors for tag selection

    public static function getPresetColors(): array
    {
        return [
            '#ef4444' => 'Red',
            '#f97316' => 'Orange',
            '#eab308' => 'Yellow',
            '#22c55e' => 'Green',
            '#14b8a6' => 'Teal',
            '#3b82f6' => 'Blue',
            '#6366f1' => 'Indigo',
            '#8b5cf6' => 'Purple',
            '#ec4899' => 'Pink',
            '#6b7280' => 'Gray',
        ];
    }
}
