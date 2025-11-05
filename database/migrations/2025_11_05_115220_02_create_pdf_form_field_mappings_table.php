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
        Schema::create('pdf_form_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('pdf_form_templates')->onDelete('cascade');
            $table->string('field_name')->comment('PDF field name from form');
            $table->string('field_type', 50)->nullable()->comment('text, checkbox, radio, dropdown, signature');
            $table->string('field_label')->nullable()->comment('Human-readable label');

            // Mapping Configuration
            $table->enum('mapping_type', ['model_field', 'custom_value', 'computed', 'unmapped'])->default('unmapped');
            $table->string('model_name', 100)->nullable()->comment('Participant, HousingJob, Property, etc.');
            $table->string('model_field', 100)->nullable()->comment('first_name, address_line_1, etc.');

            // For nested/related data
            $table->string('relationship_path')->nullable()->comment('household.income, property.full_address');

            // For custom/static values
            $table->text('custom_value')->nullable();

            // For computed values
            $table->string('computed_method')->nullable()->comment('formatPhoneNumber, calculateAge, etc.');

            // Field metadata from PDF
            $table->json('field_flags')->nullable()->comment('FieldFlags, FieldJustification, etc.');
            $table->json('field_options')->nullable()->comment('For dropdown/radio options');

            // Validation
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable()->comment('Laravel validation rules');

            // Positioning (for future visual mapper)
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('page_number')->nullable();

            $table->integer('display_order')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['template_id', 'field_name'], 'unique_template_field');
            $table->index('model_name');
            $table->index('mapping_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_form_field_mappings');
    }
};
