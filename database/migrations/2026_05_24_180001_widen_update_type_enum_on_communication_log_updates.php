<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Comm Log v2 (§5.4 / Phase 6): the Activity composer can now write
 * five user-pickable kinds (Note, Follow-up, Action, Email) plus the
 * system-only Status. Existing column had: note, follow_up,
 * status_change, outcome, other. We add: action, email — and keep
 * outcome + other as legacy values so historical updates keep
 * round-tripping. The blade partial maps both back to display labels.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE communication_log_updates MODIFY COLUMN update_type '
            ."ENUM('note','follow_up','status_change','outcome','other','action','email') "
            ."NOT NULL DEFAULT 'note'"
        );
    }

    public function down(): void
    {
        // Reverse mapping: action → note, email → note (the closest
        // pre-v2 displayable kind without dropping data).
        DB::table('communication_log_updates')->where('update_type', 'action')->update(['update_type' => 'note']);
        DB::table('communication_log_updates')->where('update_type', 'email')->update(['update_type' => 'note']);

        DB::statement(
            'ALTER TABLE communication_log_updates MODIFY COLUMN update_type '
            ."ENUM('note','follow_up','status_change','outcome','other') "
            ."NOT NULL DEFAULT 'note'"
        );
    }
};
