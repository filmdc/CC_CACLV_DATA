<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Income eligibility: the figures behind the `income-eligibility`
 * intake requirement.
 *
 * One row per CommunicationLog (the intake), holding what the worker
 * captured plus the poverty determination that fell out of it. The
 * determination columns are a SNAPSHOT, not a live view: the HHS
 * guidelines get replaced every January, and an intake qualified in
 * 2025 must keep reading as it read in 2025. guideline_year /
 * guideline_region record which table the numbers came from.
 *
 * income_sources is JSON (matching the participants/households
 * convention for income breakdowns): a list of
 * {type, label, amount} entries denominated in `entry_period`.
 * When it is empty the worker entered a single lump total in
 * `entered_amount` instead — both paths resolve to annual_income.
 *
 * The supporting documents (pay stubs, tax returns, benefit letters)
 * do NOT live here: they hang off the IntakeRequirement's existing
 * Spatie 'documents' collection, tagged by custom property.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_income_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('communication_log_id')
                ->constrained('communication_logs')
                ->cascadeOnDelete();

            // The checklist row this assessment satisfies. Nullable so a
            // deleted/orphaned requirement never takes the figures with it.
            $table->foreignId('intake_requirement_id')
                ->nullable()
                ->constrained('intake_requirements')
                ->nullOnDelete();

            $table->unsignedSmallInteger('household_size')->nullable();

            // How the worker typed the amounts. Storage is always both.
            $table->enum('entry_period', ['monthly', 'annual'])->default('annual');

            $table->json('income_sources')->nullable();
            $table->decimal('entered_amount', 12, 2)->nullable();

            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('annual_income', 12, 2)->nullable();

            // Determination snapshot.
            $table->year('guideline_year')->nullable();
            $table->string('guideline_region', 32)->default('48_contiguous');
            $table->decimal('poverty_threshold', 12, 2)->nullable();
            $table->decimal('poverty_percentage', 8, 2)->nullable();
            $table->unsignedSmallInteger('poverty_bracket')->nullable();
            $table->timestamp('calculated_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // One assessment per intake.
            $table->unique('communication_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_income_assessments');
    }
};
