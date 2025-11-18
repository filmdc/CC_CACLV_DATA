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
        if (!Schema::hasTable('conditional_code_mappings')) {
            Schema::create('conditional_code_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('campaign_code')->comment('DonorPerfect campaign code that triggers this rule (e.g., FB, SSS_FY23-24)');
                $table->enum('field_type', ['solicit_code', 'sub_solicit_code'])->comment('Which field type this mapping applies to');
                $table->string('dp_code')->comment('DonorPerfect code value (e.g., ANNLFUND, FBHPAPPL)');
                $table->string('intacct_code')->comment('Sage Intacct code to map to (e.g., F8000, S14999)');
                $table->boolean('is_active')->default(true)->comment('Whether this rule is currently active');
                $table->integer('priority')->default(0)->comment('Priority when multiple rules match (higher = higher priority)');
                $table->text('notes')->nullable()->comment('Business logic explanation for this rule');
                $table->timestamps();

                // Unique constraint: one mapping per campaign+field+code combination
                $table->unique(['campaign_code', 'field_type', 'dp_code'], 'unique_conditional_mapping');

                // Indexes for efficient querying (with explicit shorter names)
                $table->index(['campaign_code', 'field_type', 'is_active'], 'idx_campaign_field_active');
                $table->index('is_active', 'idx_is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conditional_code_mappings');
    }
};
