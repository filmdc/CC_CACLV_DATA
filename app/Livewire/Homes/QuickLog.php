<?php

namespace App\Livewire\Homes;

use App\Models\CommunicationLog;
use App\Models\Participant;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Quick-log modal — "log a call in 3 answers" (who · what · follow-up),
 * with details behind a disclosure. Present on every housing-portal page
 * via @livewire('homes.quick-log'); opened by the 'homes-open-quick-log'
 * browser event. save() writes a real CommunicationLog so entries are
 * identical to those created in the main app.
 */
class QuickLog extends Component
{
    use WithPermissionChecks;

    public bool $show = false;

    // 3 core answers
    public string $who = '';

    public string $what = '';

    public string $followUp = 'No follow-up';

    public string $followUpWhen = '1 week';

    // Progressive details
    public bool $showDetails = false;

    public string $address = '';

    public string $city = '';

    public string $state = 'PA';

    public string $zip = '';

    public string $phone = '';

    public string $email = '';

    public string $role = '';

    public ?int $matchedParticipantId = null;

    protected array $followUpOptions = ['No follow-up', 'Call back', "They're sending documents", 'Send application'];

    protected array $whenMap = [
        'tomorrow' => 1,
        '3 days' => 3,
        '1 week' => 7,
        '2 weeks' => 14,
        '1 month' => 30,
    ];

    #[On('homes-open-quick-log')]
    public function open(): void
    {
        $this->authorizeAction('communications_log.create');
        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function resetForm(): void
    {
        $this->reset(['who', 'what', 'followUp', 'followUpWhen', 'showDetails', 'address', 'city', 'state', 'zip', 'phone', 'email', 'role', 'matchedParticipantId']);
        $this->followUp = 'No follow-up';
        $this->followUpWhen = '1 week';
        $this->state = 'PA';
    }

    public function setFollowUp(string $option): void
    {
        $this->followUp = in_array($option, $this->followUpOptions, true) ? $option : 'No follow-up';
    }

    /** Suggest an existing participant match as the name is typed. */
    public function updatedWho(): void
    {
        $this->matchedParticipantId = null;
        $name = trim($this->who);
        if (strlen($name) < 3) {
            return;
        }

        $match = Participant::query()
            ->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$name}%"])
            ->first();

        $this->matchedParticipantId = $match?->id;
    }

    public function save()
    {
        $this->authorizeAction('communications_log.create');

        $this->validate([
            'who' => 'required|string|max:255',
            'what' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
        ], [], ['who' => 'name']);

        // Split the typed name into first / last.
        [$first, $last] = $this->splitName($this->who);

        // Follow-up → action + date.
        $followUpAction = null;
        $followUpDate = null;
        if ($this->followUp !== 'No follow-up') {
            $followUpAction = $this->followUp;
            $days = $this->whenMap[$this->followUpWhen] ?? 7;
            $followUpDate = now()->addDays($days)->toDateString();
        }

        $log = new CommunicationLog([
            'participant_id' => $this->matchedParticipantId,
            'communication_date' => now(),
            'communication_type' => 'phone_call',
            'direction' => 'outgoing',
            'contact_first_name' => $first ?: null,
            'contact_last_name' => $last ?: null,
            'contact_phone' => $this->phone ?: null,
            'contact_email' => $this->email ?: null,
            'contact_address' => trim($this->address) ?: null,
            'contact_city' => trim($this->city) ?: null,
            'contact_state' => strtoupper(trim($this->state)) ?: null,
            'contact_zip' => trim($this->zip) ?: null,
            'contact_role' => $this->normalizeRole($this->role),
            'subject' => 'Call with '.trim($this->who),
            'summary' => $this->what ?: null,
            'category' => 'general',
            'priority' => 'normal',
            'follow_up_action' => $followUpAction,
            'follow_up_date' => $followUpDate,
            'created_by' => Auth::id(),
        ]);
        $log->save();

        // Keep category consistent with the main app's auto-category rules.
        $log->applyAutoCategory();

        $this->show = false;
        $this->resetForm();

        $this->dispatch('homes-call-logged');
        session()->flash('homes_message', 'Call logged.');
    }

    protected function splitName(string $name): array
    {
        $name = trim($name);
        if (str_contains($name, ',')) {
            $parts = explode(',', $name, 2);

            return [trim($parts[1] ?? ''), trim($parts[0] ?? '')];
        }
        $parts = explode(' ', $name, 2);

        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }

    protected function normalizeRole(string $role): ?string
    {
        $allowed = ['homeowner', 'contractor', 'inspector', 'vendor', 'agency', 'applicant', 'family_member', 'other'];
        $role = Str::of($role)->lower()->replace(' ', '_')->toString();

        return in_array($role, $allowed, true) ? $role : null;
    }

    public function render()
    {
        return view('livewire.homes.quick-log', [
            'followUpOptions' => $this->followUpOptions,
            'whenOptions' => array_keys($this->whenMap),
        ]);
    }
}
