<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contract extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Auditable, HasFactory;

    public $table = 'contracts';

    protected $appends = [
        'documents',
    ];

    protected $dates = [
        'date_of_award',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static $searchable = [
        'contract_number',
        'date_of_award',
        'amount',
        'start_date',
        'end_date',
        'documents',
        'purpose',
        'grant_code',
        'notes',
        'title',
        'cfda_number',
    ];

    protected $fillable = [
        'contract_number',
        'date_of_award',
        'funder_id',
        'amount',
        'start_date',
        'end_date',
        'purpose',
        'grant_code',
        'notes',
        'title',
        'cfda_number',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public function contractReportDueDates()
    {
        return $this->hasMany(ReportDueDate::class, 'contract_id', 'id');
    }

    public function reportDueDates()
    {
        return $this->hasMany(ReportDueDate::class, 'contract_id', 'id'); 
    }

    public function contractReports()
    {
        return $this->hasMany(Report::class, 'contract_id', 'id');
    }

    public function getDateOfAwardAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDateOfAwardAttribute($value)
    {
        $this->attributes['date_of_award'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class);
    }

    public function funder()
    {
        return $this->belongsTo(Funder::class, 'funder_id');
    }

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }
}
