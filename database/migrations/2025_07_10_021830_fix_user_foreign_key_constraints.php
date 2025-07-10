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
        // Based on the query results, only fix the ones that have NO ACTION
        
        // Fix custom_import_logs table
        Schema::table('custom_import_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Fix contractor_documents table
        Schema::table('contractor_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix contractors table
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix housing_jobs table
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix job_contact_ledgers table
        Schema::table('job_contact_ledgers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix job_document_checklists table
        Schema::table('job_document_checklists', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['verified_by']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix property_documents table
        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });

        // Fix property_notes table
        Schema::table('property_notes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all changes back to NO ACTION
        
        Schema::table('custom_import_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('contractor_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('job_contact_ledgers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::table('job_document_checklists', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['verified_by']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::table('property_notes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};