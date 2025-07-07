<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportDueDate;
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingInvite;
use Carbon\Carbon;

class SendMeetingInvites extends Command
{
    protected $signature = 'send:meeting-invites';
    protected $description = 'Send meeting invites for each report due date';

    public function handle()
    {
        $dueDates = ReportDueDate::with('contract')->get(); // Eager load associated contracts

        foreach ($dueDates as $dueDate) {
            Mail::to('scheduling@communityactionlv.org')
                ->send(new MeetingInvite($dueDate));
        }

        $this->info('Meeting invites sent successfully!');
    }
}
