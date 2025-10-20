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
        Schema::create('sage_intacct_sync_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_history_id')->constrained('sage_intacct_sync_history')->onDelete('cascade');
            $table->foreignId('gift_cache_id')->nullable()->constrained('donor_perfect_gift_cache')->onDelete('set null');
            $table->string('gift_id'); // DonorPerfect gift_id
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('intacct_record_id')->nullable(); // Intacct's record ID if successful
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Indexes for performance and lookups
            $table->index('sync_history_id');
            $table->index('gift_cache_id');
            $table->index('gift_id');
            $table->index('status');
            $table->index('synced_at');
            $table->index('intacct_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sage_intacct_sync_items');
    }
};
