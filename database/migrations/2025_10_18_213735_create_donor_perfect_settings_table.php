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
        Schema::create('donor_perfect_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_url')->nullable();
            $table->text('api_key')->nullable(); // Encrypted
            $table->string('api_login')->nullable(); // Encrypted
            $table->text('api_password')->nullable(); // Encrypted
            $table->json('selected_fields')->nullable(); // For storing which donor/gift fields to display
            $table->integer('cache_duration')->default(15); // Minutes to cache API responses
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_connection_test')->nullable();
            $table->string('connection_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_perfect_settings');
    }
};
