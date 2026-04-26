<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            // Nullable FK on the parent table makes "zero-or-one primary
            // per project" structural — MySQL has no partial unique index
            // that could enforce the same shape on the pivot.
            $table->foreignId('primary_fund_code_id')
                ->nullable()
                ->after('funding_source')
                ->constrained('fund_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_fund_code_id');
        });
    }
};
