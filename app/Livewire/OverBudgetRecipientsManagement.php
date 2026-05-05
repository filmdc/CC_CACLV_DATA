<?php

namespace App\Livewire;

use App\Models\OverBudgetEmailRecipient;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Phase 3B Step 7 — admin page that manages the over-budget email
 * recipient list. Add active users; remove with one click. The DB
 * unique on user_id dedupes at the storage layer; this UI just
 * surfaces a clean affordance.
 *
 * Permission gate: over_budget_recipients.manage (added by the
 * Step 7 migration). NOT auto-attached to any role; admins grant
 * it explicitly via the role-permission UI.
 */
class OverBudgetRecipientsManagement extends Component
{
    use WithPermissionChecks;

    public $userIdToAdd = '';

    public function mount(): void
    {
        $this->authorizeAction('over_budget_recipients.manage');
    }

    public function addRecipient(): void
    {
        $this->authorizeAction('over_budget_recipients.manage');

        $this->validate([
            'userIdToAdd' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($this->userIdToAdd);

        if (! $user->active) {
            session()->flash('error', 'Inactive users cannot be added to the recipient list.');

            return;
        }

        // DB unique on user_id is the canonical dedupe; firstOrCreate
        // gives us a clean idempotent UI affordance regardless.
        OverBudgetEmailRecipient::firstOrCreate(
            ['user_id' => $user->id],
            ['created_by' => Auth::id()]
        );

        $this->userIdToAdd = '';
        session()->flash('success', "{$user->name} added to the over-budget recipient list.");
    }

    public function removeRecipient(int $recipientId): void
    {
        $this->authorizeAction('over_budget_recipients.manage');

        $recipient = OverBudgetEmailRecipient::with('user')->findOrFail($recipientId);
        $name = $recipient->user?->name ?? 'Recipient';
        $recipient->delete();

        session()->flash('success', "{$name} removed from the over-budget recipient list.");
    }

    public function render()
    {
        $recipients = OverBudgetEmailRecipient::with(['user', 'createdBy'])
            ->get()
            ->sortBy(fn ($r) => $r->user?->name ?? '')
            ->values();

        $candidateUsers = User::where('active', true)
            ->whereNotIn('id', $recipients->pluck('user_id')->all())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.over-budget-recipients-management', [
            'recipients' => $recipients,
            'candidateUsers' => $candidateUsers,
        ]);
    }
}
