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
            if (!Schema::hasColumn('contract_report_due_dates', 'due_time')) {
                $table->time('due_time')->nullable()->after('due_date');
            }
            
            // Legacy individual reminder fields (instead of current reminder_intervals array)
            if (!Schema::hasColumn('contract_report_due_dates', 'first_reminder')) {
                $table->integer('first_reminder')->nullable()->after('reminder_intervals');
            }
            if (!Schema::hasColumn('contract_report_due_dates', 'second_reminder')) {
                $table->integer('second_reminder')->nullable()->after('first_reminder');
            }
            if (!Schema::hasColumn('contract_report_due_dates', 'final_reminder')) {
                $table->integer('final_reminder')->nullable()->after('second_reminder');
            }
            
            // Calendar integration fields
            if (!Schema::hasColumn('contract_report_due_dates', 'invite_sent')) {
                $table->boolean('invite_sent')->default(false)->after('reminder_sent_at');
            }
            if (!Schema::hasColumn('contract_report_due_dates', 'microsoft_event_id')) {
                $table->string('microsoft_event_id')->nullable()->after('invite_sent');
            }
            
            // Recurrence fields
            if (!Schema::hasColumn('contract_report_due_dates', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('microsoft_event_id');
            }
            if (!Schema::hasColumn('contract_report_due_dates', 'recurrence_pattern')) {
                $table->string('recurrence_pattern')->nullable()->after('is_recurring');
            }
            
            // Legacy reminder emails field (text field vs current separate table approach)
            if (!Schema::hasColumn('contract_report_due_dates', 'reminder_emails')) {
                $table->text('reminder_emails')->nullable()->after('recurrence_pattern');
            }
        });

        // Add indexes outside Schema::table to avoid duplicate creation errors
        if (Schema::hasColumn('contract_report_due_dates', 'invite_sent') && !$this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_invite_sent_index')) {
            Schema::table('contract_report_due_dates', function (Blueprint $table) {
                $table->index('invite_sent');
            });
        }
        if (Schema::hasColumn('contract_report_due_dates', 'is_recurring') && !$this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_is_recurring_index')) {
            Schema::table('contract_report_due_dates', function (Blueprint $table) {
                $table->index('is_recurring');
            });
        }
        if (Schema::hasColumn('contract_report_due_dates', 'due_time') && !$this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_due_date_due_time_index')) {
            Schema::table('contract_report_due_dates', function (Blueprint $table) {
                $table->index(['due_date', 'due_time']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            if ($this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_invite_sent_index')) {
                $table->dropIndex('contract_report_due_dates_invite_sent_index');
            }
            if ($this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_is_recurring_index')) {
                $table->dropIndex('contract_report_due_dates_is_recurring_index');
            }
            if ($this->hasIndex('contract_report_due_dates', 'contract_report_due_dates_due_date_due_time_index')) {
                $table->dropIndex('contract_report_due_dates_due_date_due_time_index');
            }
            
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

    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $tableName = $connection->getTablePrefix() . $table;
        $indexes = $connection->select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);

        return !empty($indexes);
    }
};
