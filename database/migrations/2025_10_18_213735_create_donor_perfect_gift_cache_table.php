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
        Schema::create('donor_perfect_gift_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('fields_hash')->nullable();
            $table->json('gift_data'); // Stored as JSON
            $table->integer('record_count')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['start_date', 'end_date']);
            $table->index('fields_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_perfect_gift_cache');
    }
};
