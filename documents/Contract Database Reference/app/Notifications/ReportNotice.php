<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ReportNotice extends Notification
{
    use Queueable;

    var $id;
    var $due_date;
    var $title;
    var $email;
    var $countdown;
    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($notice)
    {
        $this->id = $notice->id;
        $this->due_date = $notice->due_date;
        $this->title = $notice->title;
        $this->email = $notice->email;
        $this->countdown = $notice->due_date->diff(Carbon::today())->days;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->greeting('Hello!')
                    ->line("Contract [$this->title](http://contracts.caclv.org/contracts/$this->id) has a report due on $this->due_date!")
                    ->line("There are this many days until the report is due: $this->countdown")
                    ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
