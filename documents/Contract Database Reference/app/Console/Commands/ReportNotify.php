<?php

namespace App\Console\Commands;
use App\Notifications\ReportNotice;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Console\Command;
use Illuminate\Broadcasting\Channel;
use DB;
use Carbon\Carbon;

class ReportNotify extends Command
{
    use Notifiable;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:reportsdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $contractsToNotify = DB::table('contracts')
                                ->join('report_due_dates','contracts.id','=','report_due_dates.contract_id')
                                ->join('due_date_email_report_due_date','report_due_dates.id','=','due_date_email_report_due_date.report_due_date_id')
                                ->join('due_date_emails','due_date_email_report_due_date.due_date_email_id','=','due_date_emails.id')
                                ->select('contracts.*','report_due_dates.due_date','due_date_emails.email')
                                ->where('report_due_dates.due_date', '>=', Carbon::today())
                                ->get();
        
            foreach($contractsToNotify as $notice) {
                $notice->due_date = Carbon::parse($notice->due_date);
                $diffInDays = $notice->due_date->diff(Carbon::today())->days;
                switch($diffInDays) {
                    
                    case 1:
                        \Notification::route('mail', $notice->email)
                        ->notify(new ReportNotice($notice));
                        break;
                    case 5:
                        \Notification::route('mail', $notice->email)
                        ->notify(new ReportNotice($notice));
                        break;
                    case 15:
                        \Notification::route('mail', $notice->email)
                        ->notify(new ReportNotice($notice));
                        break;
                    case 30:
                        \Notification::route('mail', $notice->email)
                        ->notify(new ReportNotice($notice));
                        break;
                    // case 4:
                    //     \Notification::route('mail', $notice->email)
                    //     ->notify(new ReportNotice($notice));
                    //     break;

                }
            }
        
        

    }
}
