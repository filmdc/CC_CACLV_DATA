<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Master toggle for utilization notifications
        SiteSetting::set('utilization_notification_enabled', '1', 'boolean');

        // Toggle to notify due date email recipients
        SiteSetting::set('utilization_notify_due_date_emails', '1', 'boolean');

        // Toggle to notify contract managers
        SiteSetting::set('utilization_notify_contract_managers', '1', 'boolean');

        // Thresholds for notifications (percentage remaining)
        SiteSetting::set('utilization_thresholds', json_encode([75, 50, 25, 10, 5, 0]), 'json');

        // Test mode settings
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::set('utilization_test_email', '', 'text');

        // Email subject template
        SiteSetting::set(
            'utilization_email_subject',
            '[{{urgency}}] Contract Budget Alert: {{contract_title}} - {{threshold}}% Remaining',
            'text'
        );

        // Email body template
        $template = <<<'EOT'
<p>Hello {{recipient_name}},</p>

<p><strong>{{urgency_message}}</strong></p>

<p>The budget for the following contract has reached a threshold requiring your attention:</p>

<table style="border-collapse: collapse; width: 100%; max-width: 500px; margin: 20px 0;">
    <tr style="background-color: #f3f4f6;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Contract</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{contract_title}}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Contract Number</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{contract_number}}</td>
    </tr>
    <tr style="background-color: #f3f4f6;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Grant Code</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{grant_code}}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Program</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{program}}</td>
    </tr>
    <tr style="background-color: #f3f4f6;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Funder</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{funder}}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Threshold Crossed</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{threshold}}% remaining</td>
    </tr>
    <tr style="background-color: #f3f4f6;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Current Utilization</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{{utilization_percentage}}%</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Total Budget</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">${{total_budget}}</td>
    </tr>
    <tr style="background-color: #f3f4f6;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Total Spent</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">${{total_spent}}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Amount Remaining</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; color: {{remaining_color}}; font-weight: bold;">${{remaining_amount}}</td>
    </tr>
</table>

{{#if recent_expenses}}
<p><strong>Recent Expenses Contributing to This Threshold:</strong></p>
<ul>
{{recent_expenses}}
</ul>
{{/if}}

<p>Please review the contract utilization and take appropriate action if needed.</p>

<p>Thank you,<br>{{site_name}} System</p>
EOT;

        SiteSetting::set('utilization_email_template', $template, 'html');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'utilization_notification_enabled',
            'utilization_notify_due_date_emails',
            'utilization_notify_contract_managers',
            'utilization_thresholds',
            'utilization_email_subject',
            'utilization_email_template',
            'utilization_test_mode',
            'utilization_test_email',
        ])->delete();
    }
};
