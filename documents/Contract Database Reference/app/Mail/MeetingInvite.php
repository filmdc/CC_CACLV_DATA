<?php

namespace App\Mail;

use App\Models\ReportDueDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingInvite extends Mailable
{
    use Queueable, SerializesModels;

    public $dueDate;

    public function __construct(ReportDueDate $dueDate)
    {
        $this->dueDate = $dueDate;
    }

    public function build()
    {
        return $this->view('frontend.dueDateEmails.meeting_invite')
            ->subject('Meeting Invitation: ' . $this->dueDate->contract->title)
            ->from('contracts@caclv.org', 'Contract Database')
            ->with([
                'dueDate' => $this->dueDate,
                'contract' => $this->dueDate->contract
            ]);
    }
}
