<?php

namespace App\Notifications;

use App\Models\MilestoneDocument;
use App\Models\HousingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MilestoneDocument $document,
        public HousingJob $job
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
        $assignedBy = $this->document->createdBy;
        $milestoneName = $this->document->milestone->name;
        $projectTitle = $this->job->job_title;
        $jobNumber = $this->job->job_number;

        $url = route('project.management', $this->job->id);

        return (new MailMessage)
            ->subject("Document Assigned: {$this->document->document_type}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$assignedBy->name} assigned you a document requirement.")
            ->line("**Project:** {$projectTitle} (#{$jobNumber})")
            ->line("**Milestone:** {$milestoneName}")
            ->line("**Document Type:** {$this->document->document_type}")
            ->lineIf($this->document->description, "**Description:** {$this->document->description}")
            ->lineIf($this->document->requires_approval, "⚠️ **This document requires approval upon upload.**")
            ->lineIf($this->document->hasPdfTemplate(), "📄 **A PDF form has been auto-generated for this document.**")
            ->action('View Project & Upload Document', $url)
            ->line('Please upload the required document as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'job_id' => $this->job->id,
            'job_title' => $this->job->job_title,
            'assigned_by' => $this->document->created_by,
        ];
    }
}
