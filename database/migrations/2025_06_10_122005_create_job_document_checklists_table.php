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
        Schema::create('job_document_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_job_id')->constrained()->onDelete('cascade');
            $table->string('document_name');
            $table->text('document_description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->datetime('uploaded_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->datetime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'uploaded', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('housing_job_id');
            $table->index('status');
            $table->index('is_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_document_checklists');
    }
};
