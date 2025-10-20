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
        Schema::create('donor_perfect_code_mappings', function (Blueprint $table) {
            $table->id();
            $table->enum('field_type', ['gl_code', 'campaign', 'solicit_code', 'sub_solicit_code']);
            $table->string('dp_code');
            $table->string('intacct_code')->nullable();
            $table->boolean('is_mapped')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate mappings
            $table->unique(['field_type', 'dp_code'], 'dp_code_mapping_unique');

            // Indexes for performance
            $table->index('field_type');
            $table->index('is_mapped');
            $table->index('is_archived');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_perfect_code_mappings');
    }
};
