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
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('internal_notes');
            $table->string('template_name')->nullable()->after('is_template');
            $table->text('template_description')->nullable()->after('template_name');
            $table->integer('times_used')->default(0)->after('template_description');

            // Index for quickly finding templates
            $table->index(['is_template', 'template_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropIndex(['is_template', 'template_name']);
            $table->dropColumn(['is_template', 'template_name', 'template_description', 'times_used']);
        });
    }
};
