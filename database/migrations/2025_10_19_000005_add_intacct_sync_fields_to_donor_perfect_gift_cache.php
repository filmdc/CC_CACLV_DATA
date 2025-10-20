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
        Schema::table('donor_perfect_gift_cache', function (Blueprint $table) {
            $table->boolean('synced_to_intacct')->default(false)->after('expires_at');
            $table->timestamp('last_synced_at')->nullable()->after('synced_to_intacct');

            // Indexes for filtering synced/unsynced records
            $table->index('synced_to_intacct');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_perfect_gift_cache', function (Blueprint $table) {
            $table->dropIndex(['synced_to_intacct']);
            $table->dropIndex(['last_synced_at']);
            $table->dropColumn(['synced_to_intacct', 'last_synced_at']);
        });
    }
};
