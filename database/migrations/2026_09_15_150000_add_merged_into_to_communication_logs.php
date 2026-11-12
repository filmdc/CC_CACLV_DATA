<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Duplicate-intake merge: the tombstone left on the record that was merged
 * away.
 *
 * The project manager sometimes logs the same homeowner twice (a second call
 * before she finds the first entry). Merging moves the duplicate's timeline,
 * documents, requirements and attachments onto the record she keeps, then
 * soft-deletes the duplicate — these three columns record where its contents
 * went, so the recycle bin can say "merged into COMM-123" rather than looking
 * like an ordinary delete.
 *
 * merged_into_id is nullOnDelete rather than cascade: force-deleting the
 * survivor should not take the historical duplicate row with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->foreignId('merged_into_id')
                ->nullable()
                ->after('converted_by')
                ->constrained('communication_logs')
                ->nullOnDelete();

            $table->timestamp('merged_at')->nullable()->after('merged_into_id');

            $table->foreignId('merged_by')
                ->nullable()
                ->after('merged_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_by');
            $table->dropColumn('merged_at');
            $table->dropConstrainedForeignId('merged_into_id');
        });
    }
};
