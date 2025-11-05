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
        Schema::create('pdf_form_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('pdf_form_templates')->onDelete('cascade');

            // Preset type
            $table->enum('preset_type', ['template_specific', 'global', 'user_custom'])->default('template_specific');

            // Complete mapping configuration
            $table->json('mapping_config')->nullable()->comment('Complete field mapping configuration');

            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false)->comment('Can other users use this preset?');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('template_id');
            $table->index('preset_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_form_presets');
    }
};
