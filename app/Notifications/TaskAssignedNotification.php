<?php

namespace App\Notifications;

use App\Models\MilestoneTask;
use App\Models\HousingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MilestoneTask $task,
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
        $assignedBy = $this->task->createdBy;
        $milestoneName = $this->task->milestone->name;
        $projectTitle = $this->job->job_title;
        $jobNumber = $this->job->job_number;
        $deadline = $this->task->deadline?->format('F j, Y') ?? 'No deadline set';

        $url = route('project.management', $this->job->id);

        return (new MailMessage)
            ->subject("Task Assigned: {$this->task->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$assignedBy->name} assigned you a task.")
            ->line("**Project:** {$projectTitle} (#{$jobNumber})")
            ->line("**Milestone:** {$milestoneName}")
            ->line("**Task:** {$this->task->name}")
            ->lineIf($this->task->description, "**Description:** {$this->task->description}")
            ->line("**Deadline:** {$deadline}")
            ->lineIf($this->task->requires_approval, "⚠️ **This task requires approval upon completion.**")
            ->action('View Project', $url)
            ->line('Please review and complete this task by the deadline.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'job_id' => $this->job->id,
            'job_title' => $this->job->job_title,
            'assigned_by' => $this->task->created_by,
        ];
    }
}
