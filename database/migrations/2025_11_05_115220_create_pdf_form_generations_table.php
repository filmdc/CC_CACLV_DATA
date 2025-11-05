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
        Schema::create('pdf_form_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('pdf_form_templates')->onDelete('cascade');

            // Polymorphic relation (what entity triggered generation)
            $table->string('generatable_type')->nullable();
            $table->unsignedBigInteger('generatable_id')->nullable();

            // Generated file details
            $table->string('file_path', 500);
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();

            // Generation metadata
            $table->integer('filled_field_count')->default(0);
            $table->integer('total_field_count')->default(0);
            $table->enum('generation_status', ['pending', 'success', 'failed', 'partial'])->default('pending');
            $table->text('error_message')->nullable();

            // Data snapshot for audit trail
            $table->json('field_data')->nullable()->comment('Complete data used to fill form');

            // Integration with milestone documents
            $table->foreignId('milestone_document_id')->nullable()->constrained('milestone_documents')->onDelete('set null');

            $table->boolean('is_flattened')->default(false)->comment('Whether form is editable or flat');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->index(['generatable_type', 'generatable_id']);
            $table->index('template_id');
            $table->index('generation_status');
            $table->index('milestone_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_form_generations');
    }
};
