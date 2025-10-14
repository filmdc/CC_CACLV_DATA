<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_report_due_date_emails', function (Blueprint $table) {
            // Add columns to track which reminders have been sent
            if (!Schema::hasColumn('contract_report_due_date_emails', 'reminders_sent')) {
                $table->json('reminders_sent')->nullable()->after('active')
                    ->comment('Array of day intervals for which reminders have been sent');
            }
            if (!Schema::hasColumn('contract_report_due_date_emails', 'last_reminder_sent')) {
                $table->timestamp('last_reminder_sent')->nullable()->after('reminders_sent')
                    ->comment('Timestamp of the last reminder sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_report_due_date_emails', function (Blueprint $table) {
            $columns = ['reminders_sent', 'last_reminder_sent'];
            $drop = array_filter($columns, fn ($column) => Schema::hasColumn('contract_report_due_date_emails', $column));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
