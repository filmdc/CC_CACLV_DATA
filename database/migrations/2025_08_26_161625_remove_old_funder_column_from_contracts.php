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
        Schema::table('contracts', function (Blueprint $table) {
            // Remove the old funder string column that conflicts with funder() relationship
            if (Schema::hasColumn('contracts', 'funder')) {
                $table->dropColumn('funder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Restore the old funder string column
            if (!Schema::hasColumn('contracts', 'funder')) {
                $table->string('funder')->nullable()->after('program_id');
            }
        });
    }
};
