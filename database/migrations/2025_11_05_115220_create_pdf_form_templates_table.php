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
        Schema::create('pdf_form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->comment('housing, participant, contract, other');
            $table->string('file_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('field_count')->default(0)->comment('Cached count of form fields');
            $table->timestamp('last_field_scan')->nullable()->comment('When fields were last extracted');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0)->comment('How many times used');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_form_templates');
    }
};
