<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): Kaitlyn's master "atom" library of requirement
 * definitions. Each row is a single, deduplicable requirement item
 * (e.g. "Photo ID", "Proof of income"). Atoms can be:
 *   - grouped into bundles via requirement_template_sets,
 *   - stamped onto an intake_requirements row as an instance.
 *
 * `key` is the dedupe slug used in via[] on instances — when the same
 * atom is pulled in via multiple bundles or services, instances dedupe
 * on this key and accumulate sources.
 *
 * Soft-deletable: removing an atom from the library does NOT cascade
 * to historical intake_requirements instances; the FK on
 * intake_requirements.source_definition_id is nullOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('key', 80);
            $table->string('label', 255);
            $table->enum('kind', ['doc', 'form', 'data', 'verify', 'external']);
            $table->boolean('default_required')->default(true);
            $table->text('hint')->nullable();

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

            // Unique only among live (non-soft-deleted) rows. MySQL doesn't
            // support partial unique indexes natively, so we add a regular
            // index on key + a unique constraint enforced by the model
            // (RequirementDefinition::rules('key', ignoringSoftDeleted)).
            $table->index('key', 'req_def_key_idx');
            $table->index('kind', 'req_def_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_definitions');
    }
};
