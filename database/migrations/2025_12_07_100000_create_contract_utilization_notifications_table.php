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
        Schema::create('contract_utilization_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->integer('threshold_percentage'); // 75, 50, 25, 10, 5, 0
            $table->decimal('utilization_at_notification', 10, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->json('recent_expenses')->nullable(); // IDs of triggering expenses
            $table->timestamp('notified_at');
            $table->string('notification_type')->default('scheduled'); // 'scheduled' | 'realtime'
            $table->timestamps();

            // Prevent duplicate notifications for same threshold
            $table->unique(['contract_id', 'threshold_percentage'], 'contract_threshold_unique');
            $table->index('notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_utilization_notifications');
    }
};
