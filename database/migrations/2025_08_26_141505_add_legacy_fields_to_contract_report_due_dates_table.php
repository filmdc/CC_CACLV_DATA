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
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            // Add time component for due dates (legacy has separate time field)
            $table->time('due_time')->nullable()->after('due_date');
            
            // Legacy individual reminder fields (instead of current reminder_intervals array)
            $table->integer('first_reminder')->nullable()->after('reminder_intervals');
            $table->integer('second_reminder')->nullable()->after('first_reminder');
            $table->integer('final_reminder')->nullable()->after('second_reminder');
            
            // Calendar integration fields
            $table->boolean('invite_sent')->default(false)->after('reminder_sent_at');
            $table->string('microsoft_event_id')->nullable()->after('invite_sent');
            
            // Recurrence fields
            $table->boolean('is_recurring')->default(false)->after('microsoft_event_id');
            $table->string('recurrence_pattern')->nullable()->after('is_recurring');
            
            // Legacy reminder emails field (text field vs current separate table approach)
            $table->text('reminder_emails')->nullable()->after('recurrence_pattern');
            
            // Add indexes for new fields
            $table->index('invite_sent');
            $table->index('is_recurring');
            $table->index(['due_date', 'due_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            $table->dropIndex(['contract_report_due_dates_invite_sent_index']);
            $table->dropIndex(['contract_report_due_dates_is_recurring_index']);
            $table->dropIndex(['contract_report_due_dates_due_date_due_time_index']);
            
            $table->dropColumn([
                'due_time',
                'first_reminder',
                'second_reminder', 
                'final_reminder',
                'invite_sent',
                'microsoft_event_id',
                'is_recurring',
                'recurrence_pattern',
                'reminder_emails'
            ]);
        });
    }
};