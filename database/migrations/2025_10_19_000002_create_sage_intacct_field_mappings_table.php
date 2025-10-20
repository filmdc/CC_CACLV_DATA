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
        Schema::create('sage_intacct_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('sage_intacct_settings')->onDelete('cascade');
            $table->string('donor_perfect_field');
            $table->string('intacct_field');
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('setting_id');
            $table->index('order');

            // Unique constraint to prevent duplicate field mappings per setting
            $table->unique(['setting_id', 'donor_perfect_field'], 'intacct_mapping_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sage_intacct_field_mappings');
    }
};
