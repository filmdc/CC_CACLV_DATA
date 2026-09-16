<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable, recoverable timeline entries.
 *
 * The "Every touch" timeline was append-only: a note typed into the wrong
 * record, or with the wrong date on it, stayed there forever. The project
 * manager and admins can now correct an entry and remove one.
 *
 * Removal is a soft delete, matching the posture the call log and the
 * project already take — the row leaves the timeline but is never
 * destroyed, and an entry removed by mistake is restorable in place from
 * the same card. deleted_by records whose removal it was.
 *
 * edited_at / edited_by are kept separate from Eloquent's updated_at so
 * the timeline can say "edited by X" truthfully: updated_at moves for any
 * write at all (a merge re-pointing the row, a backfill), while these two
 * move only when someone rewrites what the entry says.
 *
 * Additive only — every existing row reads exactly as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_log_updates', function (Blueprint $t) {
            $t->softDeletes();
            $t->foreignId('deleted_by')->nullable()->after('deleted_at')
                ->constrained('users')->nullOnDelete();
            $t->timestamp('edited_at')->nullable()->after('created_by');
            $t->foreignId('edited_by')->nullable()->after('edited_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('communication_log_updates', function (Blueprint $t) {
            $t->dropConstrainedForeignId('edited_by');
            $t->dropColumn('edited_at');
            $t->dropConstrainedForeignId('deleted_by');
            $t->dropSoftDeletes();
        });
    }
};
