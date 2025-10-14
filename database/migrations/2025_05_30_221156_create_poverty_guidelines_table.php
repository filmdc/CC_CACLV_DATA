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
        if (!Schema::hasTable('poverty_guidelines')) {
            Schema::create('poverty_guidelines', function (Blueprint $table) {
                $table->id();
                $table->year('year');
                $table->enum('region', ['48_contiguous', 'alaska', 'hawaii']);
                $table->integer('family_size');
                $table->decimal('poverty_50', 10, 2);
                $table->decimal('poverty_75', 10, 2);
                $table->decimal('poverty_100', 10, 2);
                $table->decimal('poverty_125', 10, 2);
                $table->decimal('poverty_130', 10, 2);
                $table->decimal('poverty_150', 10, 2);
                $table->decimal('poverty_200', 10, 2);
                $table->decimal('poverty_250', 10, 2);
                $table->decimal('poverty_300', 10, 2);
                $table->decimal('poverty_400', 10, 2);
                $table->decimal('poverty_500', 10, 2);
                $table->enum('period', ['monthly', 'annual']);
                $table->timestamps();

                $table->unique(['year', 'region', 'family_size', 'period']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poverty_guidelines');
    }
};
