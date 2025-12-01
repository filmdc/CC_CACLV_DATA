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
        Schema::create('documentation_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('documentation_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_helpful');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'is_helpful']);
            $table->unique(['article_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_article_feedback');
    }
};
