<?php

namespace App\Notifications;

use App\Models\PendingApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalActionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PendingApproval $pendingApproval,
        public string $action
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
        $approver = $this->pendingApproval->approver;
        $itemType = $this->pendingApproval->item_type;
        $itemName = $this->pendingApproval->item_name;
        $actionedDate = $this->pendingApproval->actioned_at?->format('F j, Y g:i A');

        if ($this->action === 'approved') {
            return $this->buildApprovedEmail($notifiable, $approver, $itemType, $itemName, $actionedDate);
        } else {
            return $this->buildRejectedEmail($notifiable, $approver, $itemType, $itemName, $actionedDate);
        }
    }

    /**
     * Build the email for approved items.
     */
    protected function buildApprovedEmail($notifiable, $approver, $itemType, $itemName, $actionedDate): MailMessage
    {
        return (new MailMessage)
            ->subject("Your {$itemType} was approved")
            ->greeting("Hello {$notifiable->name},")
            ->line("Good news! Your {$itemType} has been approved.")
            ->line("**Item:** {$itemName}")
            ->line("**Approved by:** {$approver->name}")
            ->line("**Approved on:** {$actionedDate}")
            ->line('You can now proceed with the next steps.')
            ->success();
    }

    /**
     * Build the email for rejected items.
     */
    protected function buildRejectedEmail($notifiable, $approver, $itemType, $itemName, $actionedDate): MailMessage
    {
        $rejectionReason = $this->pendingApproval->rejection_reason ?? 'No reason provided';

        // Generate upload URL based on approvable type
        $uploadUrl = $this->getUploadUrl();

        $message = (new MailMessage)
            ->subject("Your {$itemType} needs revision")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$itemType} requires revision before approval.")
            ->line("**Item:** {$itemName}")
            ->line("**Rejected by:** {$approver->name}")
            ->line("**Rejected on:** {$actionedDate}")
            ->line("**Reason:** {$rejectionReason}")
            ->line("Please review the feedback and resubmit your {$itemType}.");

        if ($uploadUrl) {
            $message->action('Upload New Version', $uploadUrl);
        }

        return $message;
    }

    /**
     * Get the upload URL based on the approvable type.
     */
    protected function getUploadUrl(): ?string
    {
        if (!$this->pendingApproval->approvable) {
            return null;
        }

        // This can be customized based on your routing structure
        $approvable = $this->pendingApproval->approvable;

        // Let the approvable model provide its own URL if it implements getApprovalResubmitUrl
        if (method_exists($approvable, 'getApprovalResubmitUrl')) {
            return $approvable->getApprovalResubmitUrl();
        }

        return match ($this->pendingApproval->approvable_type) {
            'App\Models\JobDocumentChecklist' => route('housing-jobs.show', [
                'job' => $approvable->housing_job_id
            ]),
            'App\Models\PropertyDocument' => route('properties.show', [
                'property' => $approvable->property_id
            ]),
            'App\Models\ContractorDocument' => route('contractors.show', [
                'contractor' => $approvable->contractor_id
            ]),
            default => null,
        };
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
            'action' => $this->action,
            'actioned_by' => $this->pendingApproval->approver_id,
        ];
    }
}
