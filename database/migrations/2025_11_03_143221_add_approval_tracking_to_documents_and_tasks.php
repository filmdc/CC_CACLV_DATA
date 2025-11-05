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
        // Add approval tracking to property_documents
        Schema::table('property_documents', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('uploaded_by');
            $table->string('approval_status')->default('pending')->after('requires_approval'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->integer('document_version')->default(1)->after('rejection_reason');
        });

        // Add approval tracking to contractor_documents
        Schema::table('contractor_documents', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('uploaded_by');
            $table->string('approval_status')->default('pending')->after('requires_approval'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->integer('document_version')->default(1)->after('rejection_reason');
        });

        // Add approval tracking to job_document_checklists
        Schema::table('job_document_checklists', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('is_required');
            $table->string('approval_status')->default('pending')->after('requires_approval'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->integer('document_version')->default(1)->after('approved_at');
        });

        // Add approval tracking to work_plan_goals
        Schema::table('work_plan_goals', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('actual_number');
            $table->string('approval_status')->default('pending')->after('requires_approval'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('completed_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'requires_approval',
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'document_version',
            ]);
        });

        Schema::table('contractor_documents', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'requires_approval',
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'document_version',
            ]);
        });

        Schema::table('job_document_checklists', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'requires_approval',
                'approval_status',
                'approved_by',
                'approved_at',
                'document_version',
            ]);
        });

        Schema::table('work_plan_goals', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'requires_approval',
                'approval_status',
                'approved_by',
                'approved_at',
                'completed_at',
                'rejection_reason',
            ]);
        });
    }
};
