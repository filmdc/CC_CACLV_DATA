<?php

namespace App\Livewire;

use App\Models\DueDateEmail;
use App\Models\User;
use Livewire\Component;

class RecipientSelector extends Component
{
    public $selectedRecipients = []; // Array of due_date_email IDs

    public $searchTerm = '';

    public $searchResults = [];

    public $showDropdown = false;

    protected $listeners = ['recipientSelected'];

    public function mount($selectedRecipients = [])
    {
        $this->selectedRecipients = $selectedRecipients;
    }

    public function updatedSearchTerm()
    {
        if (strlen($this->searchTerm) < 2) {
            $this->searchResults = [];
            $this->showDropdown = false;

            return;
        }

        // Search both users and distribution lists
        $search = $this->searchTerm;

        // Get users with their due_date_email records
        $userEmails = User::where('active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($user) {
                // Check if user has a due_date_email record
                $dueDateEmail = DueDateEmail::where('user_id', $user->id)->first();

                return [
                    'id' => $dueDateEmail?->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'type' => 'user',
                    'has_email_record' => $dueDateEmail !== null,
                    'user_id' => $user->id,
                ];
            });

        // Get distribution lists
        $distributionLists = DueDateEmail::whereNull('user_id')
            ->where('active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($list) {
                return [
                    'id' => $list->id,
                    'email' => $list->email,
                    'name' => $list->name,
                    'type' => 'distribution-list',
                    'has_email_record' => true,
                ];
            });

        $this->searchResults = $userEmails->merge($distributionLists)->toArray();
        $this->showDropdown = true;
    }

    public function selectRecipient($resultIndex)
    {
        $result = $this->searchResults[$resultIndex] ?? null;

        if (! $result) {
            return;
        }

        // If user doesn't have due_date_email record, create it
        if ($result['type'] === 'user' && ! $result['has_email_record']) {
            $dueDateEmail = DueDateEmail::create([
                'user_id' => $result['user_id'],
                'email' => $result['email'],
                'name' => $result['name'],
                'active' => true,
                'preferences' => DueDateEmail::getDefaultPreferences(),
                'created_by' => auth()->id(),
            ]);

            $result['id'] = $dueDateEmail->id;
        }

        // Add to selected recipients if not already there
        if ($result['id'] && ! in_array($result['id'], $this->selectedRecipients)) {
            $this->selectedRecipients[] = $result['id'];
        }

        // Dispatch event to parent component
        $this->dispatch('recipientsUpdated', $this->selectedRecipients);

        // Reset search
        $this->searchTerm = '';
        $this->searchResults = [];
        $this->showDropdown = false;
    }

    public function removeRecipient($recipientId)
    {
        $this->selectedRecipients = array_values(array_filter($this->selectedRecipients, fn ($id) => $id != $recipientId));
        $this->dispatch('recipientsUpdated', $this->selectedRecipients);
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function render()
    {
        // Get full data for selected recipients
        $selectedRecipientData = DueDateEmail::whereIn('id', $this->selectedRecipients)
            ->get()
            ->map(function ($email) {
                return [
                    'id' => $email->id,
                    'name' => $email->name,
                    'email' => $email->email,
                    'type' => $email->user_id ? 'user' : 'distribution-list',
                ];
            });

        return view('livewire.recipient-selector', [
            'selectedRecipientData' => $selectedRecipientData,
        ]);
    }
}
