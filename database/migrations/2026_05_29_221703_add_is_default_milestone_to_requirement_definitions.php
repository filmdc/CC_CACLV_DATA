<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone redesign (Phase 1): admin-curated flag for which library
 * atoms auto-seed as milestones on every newly created HousingJob.
 *
 * Typically set on Phase 2 (service_delivery_compliance) atoms that
 * represent universal compliance gates (Environmental Review, Lead
 * Paint Clearance, etc.) — items that apply regardless of which fund
 * code the project draws against.
 *
 * Default false: existing atoms stay opt-in. The Requirements Library
 * page exposes the toggle so Kaitlyn can flip atoms on as the universal
 * set evolves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_definitions', function (Blueprint $table) {
            $table->boolean('is_default_milestone')
                ->default(false)
                ->after('default_required');

            $table->index(
                ['is_default_milestone', 'deleted_at'],
                'req_def_default_milestone_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('requirement_definitions', function (Blueprint $table) {
            $table->dropIndex('req_def_default_milestone_idx');
            $table->dropColumn('is_default_milestone');
        });
    }
};
