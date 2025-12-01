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
        Schema::create('documentation_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->foreignId('category_id')->constrained('documentation_categories')->cascadeOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_internal')->default(false);
            $table->boolean('show_in_help_widget')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('current_version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'is_internal']);
            $table->fullText(['title', 'summary', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_articles');
    }
};
