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
        $addDateOfAward = !Schema::hasColumn('contracts', 'date_of_award');
        $addFunderId = !Schema::hasColumn('contracts', 'funder_id');
        $addGrantCode = !Schema::hasColumn('contracts', 'grant_code');
        $addArchived = !Schema::hasColumn('contracts', 'archived');
        $addDateArchived = !Schema::hasColumn('contracts', 'date_archived');
        $addCreatedBy = !Schema::hasColumn('contracts', 'created_by');
        $addUpdatedBy = !Schema::hasColumn('contracts', 'updated_by');

        Schema::table('contracts', function (Blueprint $table) use (
            $addDateOfAward,
            $addFunderId,
            $addGrantCode,
            $addArchived,
            $addDateArchived,
            $addCreatedBy,
            $addUpdatedBy
        ) {
            // Add new fields from reference system when missing
            if ($addDateOfAward) {
                $table->date('date_of_award')->nullable()->after('cfda_number');
            }

            if ($addFunderId) {
                $table->unsignedBigInteger('funder_id')->nullable()->after('program_id');
            }

            if ($addGrantCode) {
                $table->string('grant_code')->nullable()->after('fund_code');
            }

            if ($addArchived) {
                $table->boolean('archived')->default(false)->after('notes');
            }

            if ($addDateArchived) {
                $table->timestamp('date_archived')->nullable()->after('archived');
            }

            // Add audit fields if missing
            if ($addCreatedBy) {
                $table->unsignedBigInteger('created_by')->nullable()->after('date_archived');
            }

            if ($addUpdatedBy) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            // Add soft deletes if not exists
            if (!Schema::hasColumn('contracts', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add foreign key constraints only when the columns were just created
            if ($addFunderId) {
                $table->foreign('funder_id')->references('id')->on('funders')->onDelete('set null');
            }

            if ($addCreatedBy) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }

            if ($addUpdatedBy) {
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }

            // Add indexes for performance (only when corresponding columns were added above)
            if ($addArchived) {
                $table->index(['archived', 'start_date']);
            }

            if ($addFunderId) {
                $table->index(['funder_id', 'program_id']);
            }

            if ($addDateOfAward) {
                $table->index('date_of_award');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
