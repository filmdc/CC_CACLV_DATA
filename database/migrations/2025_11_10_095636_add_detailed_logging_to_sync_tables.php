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
        // Add detailed logging fields to sage_intacct_sync_history
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->date('sync_start_date')->nullable()->after('completed_at');
            $table->date('sync_end_date')->nullable()->after('sync_start_date');
            $table->unsignedBigInteger('executed_by_user_id')->nullable()->after('triggered_by');
            $table->json('environment_info')->nullable()->after('executed_by_user_id');
            $table->json('configuration_snapshot')->nullable()->after('environment_info');
            $table->integer('skipped_count')->default(0)->after('failed_count');
            $table->text('sync_notes')->nullable()->after('error_message');

            // Add foreign key for executed_by_user_id
            $table->foreign('executed_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Add index for date range queries
            $table->index(['sync_start_date', 'sync_end_date']);
        });

        // Add detailed logging fields to sage_intacct_sync_items
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->json('gift_data')->nullable()->after('gift_id');
            $table->json('transformed_data')->nullable()->after('gift_data');
            $table->json('api_request')->nullable()->after('transformed_data');
            $table->json('api_response')->nullable()->after('api_request');
            $table->json('validation_errors')->nullable()->after('error_message');
            $table->string('skip_reason')->nullable()->after('validation_errors');
            $table->integer('retry_count')->default(0)->after('skip_reason');
            $table->timestamp('last_retry_at')->nullable()->after('retry_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->dropForeign(['executed_by_user_id']);
            $table->dropIndex(['sync_start_date', 'sync_end_date']);
            $table->dropColumn([
                'sync_start_date',
                'sync_end_date',
                'executed_by_user_id',
                'environment_info',
                'configuration_snapshot',
                'skipped_count',
                'sync_notes',
            ]);
        });

        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropColumn([
                'gift_data',
                'transformed_data',
                'api_request',
                'api_response',
                'validation_errors',
                'skip_reason',
                'retry_count',
                'last_retry_at',
            ]);
        });
    }
};
