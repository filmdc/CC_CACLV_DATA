<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractReportDueDateEmail extends Pivot
{
    protected $table = 'contract_report_due_date_emails';

    protected $fillable = [
        'contract_report_due_date_id',
        'due_date_email_id',
        'reminder_intervals',
        'active',
        'reminders_sent',
        'last_reminder_sent',
    ];

    protected $casts = [
        'reminder_intervals' => 'array',
        'active' => 'boolean',
        'reminders_sent' => 'array',
        'last_reminder_sent' => 'datetime',
    ];

    /**
     * Get the contract report due date
     */
    public function contractReportDueDate(): BelongsTo
    {
        return $this->belongsTo(ContractReportDueDate::class);
    }

    /**
     * Get the due date email
     */
    public function dueDateEmail(): BelongsTo
    {
        return $this->belongsTo(DueDateEmail::class);
    }

    /**
     * Get the user associated with this email
     */
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            DueDateEmail::class,
            'id',
            'email',
            'due_date_email_id',
            'email'
        );
    }
}
