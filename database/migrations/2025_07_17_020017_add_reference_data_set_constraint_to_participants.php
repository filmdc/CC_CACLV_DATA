<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column exists
        if (Schema::hasColumn('participants', 'reference_data_set_id')) {
            // Check if foreign key already exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'participants' 
                AND COLUMN_NAME = 'reference_data_set_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            // Only add the foreign key if it doesn't already exist
            if (count($foreignKeys) == 0) {
                Schema::table('participants', function (Blueprint $table) {
                    $table->foreign('reference_data_set_id')
                        ->references('id')
                        ->on('reference_data_sets')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // Check if the foreign key exists before trying to drop it
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'participants' 
                AND COLUMN_NAME = 'reference_data_set_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (count($foreignKeys) > 0) {
                $table->dropForeign(['reference_data_set_id']);
            }
        });
    }
};
