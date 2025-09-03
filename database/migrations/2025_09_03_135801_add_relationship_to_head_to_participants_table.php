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
            $table->enum('relationship_to_head', [
                'self',
                'spouse',
                'child',
                'parent',
                'sibling',
                'grandchild',
                'grandparent',
                'other_relative',
                'unrelated_individual'
            ])->nullable()->after('is_head_of_household');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('relationship_to_head');
        });
    }
};
