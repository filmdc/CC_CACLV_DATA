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
        Schema::create('property_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('document_name');
            $table->text('document_description')->nullable();
            $table->enum('document_type', [
                'deed',
                'title',
                'insurance',
                'inspection',
                'assessment',
                'photo',
                'permit',
                'contract',
                'other'
            ])->default('other');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->date('document_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('property_id');
            $table->index('document_type');
            $table->index('document_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_documents');
    }
};
