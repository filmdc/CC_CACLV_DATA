<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'title',
        'contract_number',
        'cfda_number',
        'purpose',
        'program_id',
        'funder',
        'amount',
        'start_date',
        'end_date',
        'documents',
        'notes',
        'fund_code'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'documents' => 'array'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
