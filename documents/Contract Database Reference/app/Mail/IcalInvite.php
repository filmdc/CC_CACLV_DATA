<?php

namespace App\Mail;

use App\Models\ReportDueDate; // Your model
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class IcalInvite extends Mailable
{
    use Queueable, SerializesModels;

    public $dueDate;

    public function __construct(ReportDueDate $dueDate)
    {
        $this->dueDate = $dueDate;
    }

    public function build()
    {
        $contract = $this->dueDate->contract; // Assuming relationship is defined

            // Convert due_date string to DateTime object
        $dueDateObject = new \DateTime($this->dueDate->due_date);

        $icalContent = $this->generateIcalContent($contract, $dueDateObject);

        $tempIcalFile = tempnam(sys_get_temp_dir(), 'ical_') . '.ics';
        file_put_contents($tempIcalFile, $icalContent);


               // Attach the iCal file
               return $this->view('emails.ical_invite')
               ->subject($contract->title)
               ->attach($tempIcalFile, [
                   'as' => 'invite.ics',
                   'mime' => 'text/calendar',
               ]);
    }

    private function generateIcalContent($contract, $dueDate)
    {
        // Here you need to generate the iCal content
        // This is a placeholder, you'll need to use a proper iCal generation method
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "SUMMARY:" . $contract->title . "\r\n";
        $ical .= "DTSTART;VALUE=DATE:" . $dueDate->format('Ymd') . "\r\n";
        $ical .= "DTEND;VALUE=DATE:" . $dueDate->format('Ymd') . "\r\n";
        $ical .= "DESCRIPTION:Program: {$contract->program}\r\n";
        $ical .= "Purpose: {$contract->purpose}\r\n";
        $ical .= "Notes: {$contract->notes}\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }
}






