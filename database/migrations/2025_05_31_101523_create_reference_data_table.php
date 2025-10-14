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
        if (!Schema::hasTable('reference_data')) {
            Schema::create('reference_data', function (Blueprint $table) {
                $table->id();
                $table->string('category'); // e.g., 'gender', 'education_level', 'ethnicity'
                $table->string('value'); // The actual option value
                $table->string('label'); // Display label for the option
                $table->text('description')->nullable(); // Optional description
                $table->integer('sort_order')->default(0); // For ordering options
                $table->boolean('active')->default(true); // For soft deactivation
                $table->timestamps();

                // Indexes for performance
                $table->index(['category', 'active']);
                $table->index(['category', 'sort_order']);

                // Ensure unique combinations
                $table->unique(['category', 'value']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_data');
    }
};
