<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): many-to-many between program services and
 * requirement bundles. When Kaitlyn tags a service on a CommunicationLog,
 * every bundle wired to that service auto-instantiates its atoms.
 *
 * Many-to-many on both sides:
 *   - One bundle can apply to multiple services (e.g. the same
 *     "ID + income docs" bundle serves both HCP and CAH services).
 *   - One service can pull from multiple bundles (e.g. HCP-FTHB pulls
 *     "ID + income" plus a homebuyer-specific bundle).
 *
 * FK constraint names are spelled explicitly because the auto-generated
 * names exceed MySQL's 64-char identifier limit on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_service_requirement_template_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('template_set_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['service_id', 'template_set_id'], 'svc_tpl_set_pk');
            $table->index('template_set_id', 'svc_tpl_set_reverse_idx');

            $table->foreign('service_id', 'svc_tpl_set_svc_fk')
                ->references('id')->on('services')
                ->cascadeOnDelete();
            $table->foreign('template_set_id', 'svc_tpl_set_tpl_fk')
                ->references('id')->on('requirement_template_sets')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'svc_tpl_set_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_service_requirement_template_sets');
    }
};
