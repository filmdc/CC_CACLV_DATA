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
        Schema::create('donor_perfect_payment_method_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('dp_payment_type')->nullable()->comment('DonorPerfect payment type value (NULL for default)');
            $table->enum('intacct_payment_method', ['Printed Check', 'Cash', 'EFT', 'Credit Card'])
                ->comment('Sage Intacct payment method value');
            $table->boolean('is_default')->default(false)->comment('Use this as fallback when no match found');
            $table->boolean('is_active')->default(true)->comment('Enable/disable this mapping');
            $table->integer('order')->default(0)->comment('Priority order for matching (lower = higher priority)');
            $table->timestamps();

            // Index for lookup performance (custom names to avoid length limit)
            $table->index(['dp_payment_type', 'is_active'], 'dp_pmt_method_type_active_idx');
            $table->index(['is_default', 'is_active'], 'dp_pmt_method_default_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_perfect_payment_method_mappings');
    }
};
