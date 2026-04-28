<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): the document-vs-task distinction is intrinsic to
     * a fund-code requirement template, not a per-project decision. A
     * MA1000 contract obligation is a document for every project that
     * attaches MA1000; flipping it per-instantiation would scale poorly.
     *
     * Default 'task' so existing templates instantiate as tasks, matching
     * the prior Phase 2 panel's labels-only display. Admin can edit each
     * template to 'document' where an upload is genuinely required.
     */
    public function up(): void
    {
        Schema::table('fund_code_requirements', function (Blueprint $table) {
            $table->enum('type', ['document', 'task'])
                ->default('task')
                ->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('fund_code_requirements', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
