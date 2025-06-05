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
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('reference_data_set_id')->after('household_id')->nullable()->constrained()->onDelete('set null');
            $table->index('reference_data_set_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['reference_data_set_id']);
            $table->dropIndex(['reference_data_set_id']);
            $table->dropColumn('reference_data_set_id');
        });
    }
};
