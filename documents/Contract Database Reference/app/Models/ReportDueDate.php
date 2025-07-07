<?php

namespace App\Models;

use \DateTimeInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReportDueDate extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use HasFactory;

    public const STATUS_SELECT = [
        'incomplete' => 'Incomplete',
        'completed'  => 'completed',
    ];

    public $table = 'report_due_dates';

    public static $searchable = [
        'due_date',
        'description',
    ];

    protected $appends = [
        'documents',
    ];

    protected $dates = [
        'due_date',
        // 'reminder_second',
        // 'reminder_third',
        // 'reminder_first',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'due_date',
        'status',
        'description',
        'contract_id',
        // 'reminder_second',
        // 'reminder_third',
        // 'reminder_first',
        // 'notified',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public function getDueDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDueDateAttribute($value)
    {
        $this->attributes['due_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    public function emails()
    {
        return $this->belongsToMany(DueDateEmail::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function getReminderSecondAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setReminderSecondAttribute($value)
    {
        $this->attributes['reminder_second'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getReminderThirdAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setReminderThirdAttribute($value)
    {
        $this->attributes['reminder_third'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getReminderFirstAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setReminderFirstAttribute($value)
    {
        $this->attributes['reminder_first'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
