<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntacctFundExpense extends Model
{
    protected $fillable = [
        'contract_id',
        'intacct_record_no',
        'intacct_project_id',
        'intacct_department_id',
        'posting_date',
        'amount',
        'gl_account',
        'gl_account_title',
        'document_no',
        'description',
        'fiscal_year',
        'raw_data',
        'last_synced_at',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'amount' => 'decimal:2',
        'fiscal_year' => 'integer',
        'raw_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeForFiscalYear($query, int $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('posting_date', [$from, $to]);
    }
}
