<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 9: a Kaitlyn-curated catalog of housing services,
 * deliberately decoupled from program services (which exist for CSBG
 * Module 4 reporting). A housing service is "what we offer this client":
 * it can be tied to one or more fund codes (for budgeting + anticipated
 * fund-code auto-checking on the Comm Log), and can carry its own set
 * of requirements (atoms + bundles).
 *
 * Soft-deletable so historical Communication Log → service references
 * survive removal of an obsolete catalog entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name', 'housing_services_name_idx');
            $table->index('is_active', 'housing_services_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_services');
    }
};
