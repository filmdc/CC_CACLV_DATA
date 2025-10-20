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
        Schema::create('sage_intacct_sync_history', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'partial'])->default('pending');
            $table->integer('total_records')->default(0);
            $table->integer('successful_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('triggered_by')->nullable(); // user_id or 'scheduler'
            $table->timestamps();

            // Indexes for performance and reporting
            $table->index('status');
            $table->index('started_at');
            $table->index('completed_at');
            $table->index('triggered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sage_intacct_sync_history');
    }
};
