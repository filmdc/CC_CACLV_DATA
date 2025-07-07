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
        Schema::create('contractor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->onDelete('cascade');
            $table->string('document_name');
            $table->text('document_description')->nullable();
            $table->enum('document_type', [
                'license',
                'insurance',
                'w9',
                'agreement',
                'certification',
                'reference',
                'other'
            ])->default('other');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->date('document_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('contractor_id');
            $table->index('document_type');
            $table->index('expiration_date');
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractor_documents');
    }
};
