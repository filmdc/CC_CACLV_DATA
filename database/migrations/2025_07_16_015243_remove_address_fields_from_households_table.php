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
        Schema::table('households', function (Blueprint $table) {
            $columns = ['street_address', 'city', 'state', 'zip_code'];
            $drop = array_filter($columns, fn ($column) => Schema::hasColumn('households', $column));

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            if (!Schema::hasColumn('households', 'street_address')) {
                $table->string('street_address')->nullable()->after('hhs_poverty_percentage');
            }
            if (!Schema::hasColumn('households', 'city')) {
                $table->string('city')->nullable()->after('street_address');
            }
            if (!Schema::hasColumn('households', 'state')) {
                $table->string('state', 2)->nullable()->after('city');
            }
            if (!Schema::hasColumn('households', 'zip_code')) {
                $table->string('zip_code', 10)->nullable()->after('state');
            }
        });
    }
};
