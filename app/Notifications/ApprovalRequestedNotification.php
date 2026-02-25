<?php

namespace App\Notifications;

use App\Models\PendingApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApprovalRequestedNotification extends Notification implements ShouldQueue
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
        $itemDescription = $this->pendingApproval->item_description;
        $programName = $this->pendingApproval->program?->name ?? 'N/A';
        $submittedDate = $this->pendingApproval->submitted_at->format('F j, Y g:i A');

        $approveUrl = URL::signedRoute('approval.approve', [
            'id' => $this->pendingApproval->id
        ]);

        $rejectUrl = URL::signedRoute('approval.reject', [
            'id' => $this->pendingApproval->id
        ]);

        $message = (new MailMessage)
            ->subject("Approval Required: {$itemName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$submitter->name} submitted {$itemType} for your approval.")
            ->line("**Item:** {$itemName}")
            ->lineIf($itemDescription, "**Description:** {$itemDescription}")
            ->lineIf($this->pendingApproval->program_id, "**Program:** {$programName}");

        // Add chain step info for multi-step approvals
        if ($this->pendingApproval->approval_type_id && $this->pendingApproval->approvalType) {
            $totalSteps = $this->pendingApproval->approvalType->getTotalSteps();
            if ($totalSteps > 1) {
                $currentStep = $this->pendingApproval->current_chain_step ?? 1;
                $chain = $this->pendingApproval->approvalType->getChainForStep($currentStep);
                $stepName = $chain?->step_name ?? "Step {$currentStep}";
                $message->line("**Approval Step:** {$stepName} ({$currentStep} of {$totalSteps})");
            }
        }

        $message->line("**Submitted on:** {$submittedDate}")
            ->action('Review & Approve', $approveUrl)
            ->action('Reject', $rejectUrl)
            ->line('Please review and take action on this approval request.');

        return $message;
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
