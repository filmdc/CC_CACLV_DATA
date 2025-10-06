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
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            // Add new fields from reference system
            $table->date('date_of_award')->nullable()->after('cfda_number');
            $table->unsignedBigInteger('funder_id')->nullable()->after('program_id');
            $table->string('grant_code')->nullable()->after('fund_code');
            $table->boolean('archived')->default(false)->after('notes');
            $table->timestamp('date_archived')->nullable()->after('archived');
            
            // Add audit fields
            $table->unsignedBigInteger('created_by')->nullable()->after('date_archived');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Add soft deletes if not exists
            if (!Schema::hasColumn('contracts', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add foreign key constraints
            $table->foreign('funder_id')->references('id')->on('funders')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['archived', 'start_date']);
            $table->index(['funder_id', 'program_id']);
            $table->index('date_of_award');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['funder_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'date_of_award',
                'funder_id',
                'grant_code',
                'archived',
                'date_archived',
                'created_by',
                'updated_by'
            ]);
        });
    }
};
