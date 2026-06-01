<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone redesign (Phase 4): soft-archive instead of hard-delete.
 *
 * Projects accumulate compliance history (documents, requirement
 * completions, audit ledger entries, expense records). The user
 * decision on 2026-05-29 was: hard-deleting a project = audit data
 * loss; the right primitive is "hide / archive" with a reason and an
 * actor stamp.
 *
 * - is_archived (bool, default false): the predicate the /projects
 *   list filters on. Indexed for the common WHERE.
 * - archived_at (timestamp, null when active): when it was archived.
 * - archived_by_user_id (FK, nullable): who archived it.
 * - archived_reason (text, nullable): why; required by the UI prompt
 *   when archiving but kept nullable at the DB layer so historical
 *   manual archives via console don't fail an INSERT.
 *
 * Restore = clear is_archived + archived_at + archived_by + reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->boolean('is_archived')
                ->default(false)
                ->after('priority');

            $table->timestamp('archived_at')
                ->nullable()
                ->after('is_archived');

            $table->foreignId('archived_by_user_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('archived_reason')
                ->nullable()
                ->after('archived_by_user_id');

            $table->index(['is_archived', 'is_template'], 'housing_jobs_archive_template_idx');
        });
    }

    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropIndex('housing_jobs_archive_template_idx');
            $table->dropConstrainedForeignId('archived_by_user_id');
            $table->dropColumn(['is_archived', 'archived_at', 'archived_reason']);
        });
    }
};
