<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Housing portal: per-user configurable list views.
 *
 * A saved view captures which columns a user wants visible and which
 * default filters apply on a portal list screen ('calls' | 'projects').
 * One view per user+screen may be flagged is_default — it is applied
 * automatically when the screen mounts with no explicit URL state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homes_saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('screen', 32);
            $table->string('name', 60);
            $table->json('columns');
            $table->json('filters');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'screen', 'name'], 'uq_homes_saved_views_name');
            $table->index(['user_id', 'screen'], 'ix_homes_saved_views_user_screen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homes_saved_views');
    }
};
