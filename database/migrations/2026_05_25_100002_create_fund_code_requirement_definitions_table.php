<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 10: many-to-many fund_codes ↔ requirement_definitions.
 *
 * Replaces the old "bundle" abstraction. Instead of grouping atoms
 * into named bundles and then attaching bundles to fund codes, atoms
 * are linked directly to fund codes here. Same outcome ("when this
 * fund code is anticipated, these requirements stamp") but flatter.
 *
 * The legacy `fund_code_requirements` table (P-39b — its own
 * label/type/phase rows) remains for back-compat with existing
 * production data. New atom-driven attachments go through this pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_code_requirement_definitions', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_code_id');
            $table->unsignedBigInteger('requirement_definition_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['fund_code_id', 'requirement_definition_id'], 'fc_req_def_pk');
            $table->index('requirement_definition_id', 'fc_req_def_reverse_idx');

            $table->foreign('fund_code_id', 'fc_req_def_fc_fk')
                ->references('id')->on('fund_codes')
                ->cascadeOnDelete();
            $table->foreign('requirement_definition_id', 'fc_req_def_def_fk')
                ->references('id')->on('requirement_definitions')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'fc_req_def_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_code_requirement_definitions');
    }
};
