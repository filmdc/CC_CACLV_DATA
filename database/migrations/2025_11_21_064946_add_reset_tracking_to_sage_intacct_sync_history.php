<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->timestamp('reset_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('reset_by_user_id')->nullable()->after('reset_at');
            $table->integer('reset_count')->default(0)->after('reset_by_user_id');

            // Add foreign key for user tracking
            $table->foreign('reset_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Add index for filtering reset syncs
            $table->index('reset_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->dropForeign(['reset_by_user_id']);
            $table->dropIndex(['reset_at']);
            $table->dropColumn(['reset_at', 'reset_by_user_id', 'reset_count']);
        });
    }
};
