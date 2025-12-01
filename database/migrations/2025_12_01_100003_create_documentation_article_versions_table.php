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
        Schema::create('documentation_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('documentation_articles')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('title', 255);
            $table->longText('content');
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'version_number']);
            $table->index(['article_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_article_versions');
    }
};
