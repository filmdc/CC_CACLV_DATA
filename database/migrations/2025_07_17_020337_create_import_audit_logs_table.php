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
        Schema::create('import_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('import_type'); // 'participant', 'hmis', 'rx_office', etc.
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->foreignId('reference_data_set_id')->nullable()->constrained('reference_data_sets')->onDelete('set null');
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->enum('status', ['processing', 'completed', 'failed', 'cancelled'])->default('processing');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('options')->nullable(); // Store import options
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('import_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_audit_logs');
    }
};