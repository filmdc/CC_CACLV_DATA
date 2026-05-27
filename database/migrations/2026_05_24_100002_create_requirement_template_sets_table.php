<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): named bundles of requirement atoms.
 *
 * A bundle ("HCP First-Time Homebuyer set") groups multiple
 * requirement_definitions and can be linked to one-or-more program
 * services. Tagging a service on a CommunicationLog auto-instantiates
 * every atom in every bundle wired to that service.
 *
 * Frozen-snapshot semantics: editing a bundle does NOT propagate back
 * to already-instantiated rows on past intake_requirements (they hold
 * source_label_snapshot + kind at instantiation time).
 *
 * Soft-deletable for the same reason as requirement_definitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_template_sets', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name', 'req_tpl_set_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_template_sets');
    }
};
