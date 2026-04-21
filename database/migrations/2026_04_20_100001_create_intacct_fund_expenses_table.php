<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intacct_fund_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();

            $table->string('intacct_record_no', 50)
                ->comment('Intacct GLDETAIL RECORDNO - globally unique within Intacct');
            $table->string('intacct_project_id', 50)
                ->comment('PROJECTID (fund code) the line was posted against');
            $table->string('intacct_department_id', 50)
                ->nullable()
                ->comment('DEPARTMENTID (service code) when filter was used');

            $table->date('posting_date');
            $table->decimal('amount', 14, 2)
                ->comment('Expense amount (debit minus credit for the line)');

            $table->string('gl_account', 20);
            $table->string('gl_account_title', 255)->nullable();
            $table->string('document_no', 100)->nullable()
                ->comment('Source document reference (bill #, journal #, etc.)');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('fiscal_year')->nullable()
                ->comment('FY this line falls within (year the FY ends, e.g. 2026 = 7/1/2025-6/30/2026)');

            $table->json('raw_data')->nullable()
                ->comment('Full Intacct response for debugging/auditing');

            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->unique(['contract_id', 'intacct_record_no'], 'intacct_fund_expenses_contract_record_unique');

            $table->index('posting_date');
            $table->index('fiscal_year');
            $table->index('intacct_project_id');
            $table->index('intacct_department_id');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intacct_fund_expenses');
    }
};
