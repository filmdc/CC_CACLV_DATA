<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('potential_duplicates', function (Blueprint $table) {
            $table->id();
            
            // Source information
            $table->string('source_type'); // 'manual', 'hmis_import', 'rx_office_import', 'general_import'
            $table->string('source_identifier')->nullable(); // Import log ID, session ID, etc.
            $table->json('proposed_data'); // The new participant data that was proposed
            
            // Matching information
            $table->foreignId('matched_participant_id')->constrained('participants')->onDelete('cascade');
            $table->decimal('confidence_score', 5, 2); // 0.00 to 100.00
            $table->json('matching_criteria'); // Which fields matched and their weights
            
            // Review and resolution
            $table->enum('status', ['pending', 'approved_merge', 'approved_separate', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('merge_instructions')->nullable(); // How to merge the data if approved
            
            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['matched_participant_id', 'status']);
            $table->index(['source_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potential_duplicates');
    }
};