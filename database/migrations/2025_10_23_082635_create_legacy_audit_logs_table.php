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
        Schema::create('legacy_audit_logs', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->text('description');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('properties')->nullable();
            $table->string('host', 46)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('legacy_contract_id')->nullable();
            $table->unsignedBigInteger('legacy_report_id')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('legacy_contract_id');
            $table->index('legacy_report_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_audit_logs');
    }
};
