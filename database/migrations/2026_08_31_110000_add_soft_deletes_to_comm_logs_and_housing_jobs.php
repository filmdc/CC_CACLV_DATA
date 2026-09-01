<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recoverable deletes for call logs and projects.
 *
 * The housing project manager can now delete a call/intake or a project from
 * the portal. The row is soft-deleted rather than destroyed: it disappears
 * from every normal query, but an admin can restore it from the recycle bin
 * (/admin/recycle-bin) if the delete was a mistake.
 *
 * deleted_by records who pressed the button, so the recycle bin can say
 * whose delete it was without digging through the activity log.
 *
 * Additive only — no existing row is touched, and models without the
 * SoftDeletes trait are unaffected by the new column.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['communication_logs', 'housing_jobs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
                $t->foreignId('deleted_by')->nullable()->after('deleted_at')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['communication_logs', 'housing_jobs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('deleted_by');
                $t->dropSoftDeletes();
            });
        }
    }
};
