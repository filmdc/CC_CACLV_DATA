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
        Schema::table('milestone_documents', function (Blueprint $table) {
            $table->foreignId('pdf_template_id')->nullable()->after('milestone_id')->constrained('pdf_form_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_documents', function (Blueprint $table) {
            $table->dropForeign(['pdf_template_id']);
            $table->dropColumn('pdf_template_id');
        });
    }
};
