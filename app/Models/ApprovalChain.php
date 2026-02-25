<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ApprovalChain extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_type_id',
        'step_number',
        'step_name',
        'required_permission',
        'required_role',
        'approver_user_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function approvalType(): BelongsTo
    {
        return $this->belongsTo(ApprovalType::class);
    }

    public function pinnedApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function getQualifiedApprovers(): Collection
    {
        // If a specific user is pinned, return just that user
        if ($this->approver_user_id) {
            $user = User::find($this->approver_user_id);

            return $user ? collect([$user]) : collect();
        }

        $query = User::query();

        // Filter by required role
        if ($this->required_role) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->required_role);
            });
        }

        // Filter by required permission
        if ($this->required_permission) {
            $query->whereHas('roles.permissions', function ($q) {
                $q->where('name', $this->required_permission);
            });
        }

        // If neither role nor permission set, fall back to users with approve_reject permission
        if (! $this->required_role && ! $this->required_permission && ! $this->approver_user_id) {
            $query->whereHas('roles.permissions', function ($q) {
                $q->where('name', 'approvals.approve_reject');
            });
        }

        return $query->get();
    }
}
