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
            if (Schema::hasColumn('contracts', 'program_id')) {
                // Drop the foreign key constraint first
                try {
                    $table->dropForeign(['program_id']);
                } catch (\Throwable $e) {
                    // ignore if already dropped
                }

                // Make program_id nullable for legacy many-to-many compatibility
                $table->unsignedBigInteger('program_id')->nullable()->change();

                // Re-add the foreign key constraint as nullable
                $table->foreign('program_id')->references('id')->on('programs')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'program_id')) {
                // Drop the foreign key constraint
                try {
                    $table->dropForeign(['program_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                // Make program_id required again
                $table->unsignedBigInteger('program_id')->nullable(false)->change();

                // Re-add the foreign key constraint as required
                $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            }
        });
    }
};
