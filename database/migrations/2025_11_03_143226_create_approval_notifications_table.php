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
        if (!Schema::hasTable('approval_notifications')) {
            Schema::create('approval_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pending_approval_id')->constrained('pending_approvals')->onDelete('cascade');
                $table->enum('notification_type', ['request', 'approved', 'rejected', 'resubmitted']);
                $table->foreignId('recipient_id')->constrained('users');
                $table->timestamp('sent_at');
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('actioned_at')->nullable();
                $table->timestamps();

                $table->index('pending_approval_id');
                $table->index('recipient_id');
                $table->index('notification_type');
                $table->index('sent_at');
                $table->index('opened_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_notifications');
    }
};
