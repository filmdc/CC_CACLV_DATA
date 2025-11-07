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
        Schema::create('user_sage_intacct_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('access_token'); // Encrypted in model
            $table->text('refresh_token')->nullable(); // Encrypted in model
            $table->timestamp('expires_at');
            $table->string('company_id')->nullable(); // Which Sage company they connected to
            $table->string('token_type')->default('Bearer');
            $table->json('scopes')->nullable(); // OAuth scopes granted
            $table->timestamps();

            // One token per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sage_intacct_tokens');
    }
};
