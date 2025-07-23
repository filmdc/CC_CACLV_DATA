<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SiteSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default contract reminder settings
        SiteSetting::set('contract_reminder_intervals', json_encode([30, 15, 7, 3, 1, 0]), 'json');
        SiteSetting::set('contract_reminder_test_mode', '0', 'boolean');
        SiteSetting::set('contract_reminder_test_email', '', 'text');
        
        // Default email subject
        SiteSetting::set('contract_reminder_email_subject', 'Contract Report Reminder: {{report_name}} - Due in {{days_until}} days', 'text');
        
        // Default email template
        $defaultTemplate = <<<'EOT'
<p>Hello,</p>

<p>This is a reminder that the following contract report is due:</p>

<ul>
<li><strong>Report Name:</strong> {{report_name}}</li>
<li><strong>Contract:</strong> {{contract_title}}</li>
<li><strong>Program:</strong> {{program}}</li>
<li><strong>Funder:</strong> {{funder}}</li>
<li><strong>Due Date:</strong> {{due_date}}</li>
<li><strong>Days Remaining:</strong> {{days_until}}</li>
</ul>

{{#if description}}
<p><strong>Description:</strong> {{description}}</p>
{{/if}}

<p>Please ensure this report is submitted on time.</p>

<p>Thank you,<br>
CAP TRACK System</p>
EOT;
        
        SiteSetting::set('contract_reminder_email_template', $defaultTemplate, 'html');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove contract reminder settings
        \DB::table('site_settings')->whereIn('key', [
            'contract_reminder_intervals',
            'contract_reminder_test_mode',
            'contract_reminder_test_email',
            'contract_reminder_email_subject',
            'contract_reminder_email_template',
        ])->delete();
    }
};