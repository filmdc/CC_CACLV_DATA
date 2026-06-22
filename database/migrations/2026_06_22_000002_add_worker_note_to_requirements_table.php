<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-requirement free-form worker note for project (Phase 2/3)
 * requirements - the project-page mirror of the intake worker note.
 *
 * Distinct from `completion_note` (captured only at completion) and
 * `description` (the static definition of the requirement). This is the
 * editable standing annotation surfaced under each requirement on the
 * milestone detail panel and editable from the requirement edit form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->text('worker_note')->nullable()->after('completion_note');
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->dropColumn('worker_note');
        });
    }
};
