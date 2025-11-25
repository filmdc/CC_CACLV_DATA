<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Notifications\NewFeedbackNotification;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;

class FeedbackWidget extends Component
{
    use WithFileUploads;
    use WithPermissionChecks;

    public bool $showModal = false;

    public string $category = 'bug_report';

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public $screenshot;

    public string $currentUrl = '';

    protected $rules = [
        'category' => 'required|in:bug_report,feature_request,usability_issue,question',
        'title' => 'required|min:5|max:255',
        'description' => 'required|min:10|max:5000',
        'priority' => 'required|in:low,medium,high,critical',
        'screenshot' => 'nullable|image|max:2048',
    ];

    protected $messages = [
        'title.required' => 'Please provide a title for your feedback.',
        'title.min' => 'The title should be at least 5 characters.',
        'description.required' => 'Please describe your feedback in detail.',
        'description.min' => 'Please provide more detail (at least 10 characters).',
    ];

    public function mount()
    {
        $this->currentUrl = request()->url();
    }

    public function openModal()
    {
        $this->currentUrl = request()->header('referer', request()->url());
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function submit()
    {
        $this->validate();

        $feedback = Feedback::create([
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => 'new',
            'page_url' => $this->currentUrl,
            'browser_info' => request()->userAgent(),
            'user_id' => auth()->id(),
            'additional_context' => [
                'screen_width' => request()->header('sec-ch-width'),
                'submitted_at' => now()->toISOString(),
            ],
        ]);

        // Handle screenshot upload
        if ($this->screenshot) {
            $feedback->addMedia($this->screenshot->getRealPath())
                ->usingFileName('screenshot-'.time().'.'.$this->screenshot->getClientOriginalExtension())
                ->toMediaCollection('screenshots');
        }

        // Send notifications to configured recipients
        $this->sendNotifications($feedback);

        $this->closeModal();
        $this->dispatch('feedback-submitted');

        session()->flash('feedback-success', 'Thank you for your feedback! We\'ll review it shortly.');
    }

    protected function sendNotifications(Feedback $feedback): void
    {
        $recipients = config('feedback.email.recipients', []);

        if (config('feedback.email.enabled') && ! empty($recipients)) {
            foreach ($recipients as $email) {
                if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                    Notification::route('mail', trim($email))
                        ->notify(new NewFeedbackNotification($feedback));
                }
            }
        }
    }

    public function resetForm()
    {
        $this->category = 'bug_report';
        $this->title = '';
        $this->description = '';
        $this->priority = 'medium';
        $this->screenshot = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.feedback-widget');
    }
}
