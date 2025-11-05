<?php

namespace App\Notifications;

use App\Models\PendingApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class DocumentResubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PendingApproval $pendingApproval
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
        $submitter = $this->pendingApproval->submittedBy;
        $itemType = $this->pendingApproval->item_type;
        $itemName = $this->pendingApproval->item_name;
        $resubmittedDate = $this->pendingApproval->submitted_at->format('F j, Y g:i A');

        $reviewUrl = URL::signedRoute('approval.approve', [
            'id' => $this->pendingApproval->id
        ]);

        return (new MailMessage)
            ->subject("{$itemType} has been resubmitted for approval")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$submitter->name} has resubmitted a {$itemType} for your approval after addressing your previous feedback.")
            ->line("**Item:** {$itemName}")
            ->line("**Resubmitted on:** {$resubmittedDate}")
            ->action('Review Resubmission', $reviewUrl)
            ->line('Please review the updated submission and take action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pending_approval_id' => $this->pendingApproval->id,
            'item_type' => $this->pendingApproval->item_type,
            'item_name' => $this->pendingApproval->item_name,
            'submitted_by' => $this->pendingApproval->submitted_by,
        ];
    }
}
