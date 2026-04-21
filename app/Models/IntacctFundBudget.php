<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fund-level budget pulled from Intacct's GLBUDGETITEM. One row per
 * (project_id, department_id) tuple, independent of contracts — so a fund
 * like F8000 has a single authoritative budget regardless of how many
 * contracts reference it, and Sage-only funds (no local contract) still
 * have a budget record.
 */
class IntacctFundBudget extends Model
{
    protected $fillable = [
        'intacct_project_id',
        'intacct_department_id',
        'intacct_project_name',
        'amount',
        'budget_source',
        'lines_count',
        'fiscal_year',
        'window_start',
        'window_end',
        'synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'lines_count' => 'integer',
        'fiscal_year' => 'integer',
        'window_start' => 'date',
        'window_end' => 'date',
        'synced_at' => 'datetime',
    ];

    /**
     * Contracts that map to this fund (matched on grant_code + service_code).
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'grant_code', 'intacct_project_id')
            ->where(function ($q) {
                $q->where('contracts.service_code', $this->intacct_department_id)
                  ->orWhere(function ($q2) {
                      if ($this->intacct_department_id === '') {
                          $q2->whereNull('contracts.service_code');
                      }
                  });
            });
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(IntacctFundExpense::class, 'intacct_project_id', 'intacct_project_id')
            ->where('intacct_department_id', $this->intacct_department_id);
    }
}
