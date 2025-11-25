<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Feedback $feedback
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $submitter = $this->feedback->user;
        $category = $this->feedback->category_label;
        $priority = $this->feedback->priority_label;
        $title = $this->feedback->title;
        $description = $this->feedback->description;
        $pageUrl = $this->feedback->page_url;
        $submittedAt = $this->feedback->created_at->format('F j, Y g:i A');

        $priorityEmoji = match ($this->feedback->priority) {
            'critical' => '[CRITICAL]',
            'high' => '[HIGH]',
            default => '',
        };

        $categoryEmoji = match ($this->feedback->category) {
            'bug_report' => '[BUG]',
            'feature_request' => '[FEATURE]',
            'usability_issue' => '[UX]',
            'question' => '[QUESTION]',
            default => '',
        };

        $viewUrl = route('feedback.management');

        return (new MailMessage)
            ->subject("{$priorityEmoji}{$categoryEmoji} New Feedback: {$title}")
            ->greeting('New Feedback Submitted')
            ->line("A new {$category} has been submitted by {$submitter->name}.")
            ->line("**Title:** {$title}")
            ->line("**Priority:** {$priority}")
            ->line("**Category:** {$category}")
            ->line('**Description:**')
            ->line($description)
            ->lineIf($pageUrl, "**Page:** {$pageUrl}")
            ->line("**Submitted:** {$submittedAt}")
            ->action('View Feedback', $viewUrl)
            ->line('Please review and respond to this feedback.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'category' => $this->feedback->category,
            'title' => $this->feedback->title,
            'priority' => $this->feedback->priority,
            'submitted_by' => $this->feedback->user_id,
        ];
    }
}
