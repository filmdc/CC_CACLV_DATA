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
        Schema::create('communication_log_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communication_log_id')->constrained('communication_logs')->onDelete('cascade');
            $table->text('update_content');
            $table->enum('update_type', ['note', 'follow_up', 'status_change', 'outcome', 'other'])->default('note');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('communication_log_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_log_updates');
    }
};
