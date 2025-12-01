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
        Schema::create('documentation_route_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 100);
            $table->string('route_label', 255)->nullable();
            $table->foreignId('article_id')->constrained('documentation_articles')->cascadeOnDelete();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique(['route_name', 'article_id']);
            $table->index('route_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_route_mappings');
    }
};
