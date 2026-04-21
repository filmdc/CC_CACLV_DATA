<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('service_code', 50)
                ->nullable()
                ->after('grant_code')
                ->comment('Intacct DEPARTMENTID when fund is shared (e.g. F8000 + S14100). Null for normal 1:1 fund_code contracts.');

            $table->index('service_code');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['service_code']);
            $table->dropColumn('service_code');
        });
    }
};
