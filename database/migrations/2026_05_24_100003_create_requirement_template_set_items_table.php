<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): bundle ↔ atom membership.
 *
 * `required_override` lets a bundle force-flip an atom's default_required
 * just for the context of that bundle (e.g. "Photo ID is normally
 * required, but in the Sixth Street bundle it's optional because we have
 * a housing-status attestation fallback").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_template_set_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('template_set_id')
                ->constrained('requirement_template_sets')
                ->cascadeOnDelete();
            $table->foreignId('requirement_definition_id')
                ->constrained('requirement_definitions')
                ->cascadeOnDelete();

            $table->boolean('required_override')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(
                ['template_set_id', 'requirement_definition_id'],
                'req_tpl_set_item_unique'
            );
            $table->index(
                ['template_set_id', 'position'],
                'req_tpl_set_item_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_template_set_items');
    }
};
