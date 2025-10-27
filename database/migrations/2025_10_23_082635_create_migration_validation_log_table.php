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
        Schema::create('migration_validation_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->unsignedBigInteger('new_id')->nullable();
            $table->string('status', 20);
            $table->json('errors')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'status']);
            $table->index('legacy_id');
            $table->index('new_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_validation_log');
    }
};
