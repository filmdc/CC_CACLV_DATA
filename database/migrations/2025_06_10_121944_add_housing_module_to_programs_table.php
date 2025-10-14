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
        if (!Schema::hasColumn('programs', 'housing_module_enabled')) {
            Schema::table('programs', function (Blueprint $table) {
                if (!Schema::hasColumn('programs', 'housing_module_enabled')) {
                    $table->boolean('housing_module_enabled')->default(false)->after('active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (Schema::hasColumn('programs', 'housing_module_enabled')) {
                $table->dropColumn('housing_module_enabled');
            }
        });
    }
};
