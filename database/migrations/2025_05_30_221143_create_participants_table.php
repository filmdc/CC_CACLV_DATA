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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->boolean('is_head_of_household')->default(false);
            
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say']);
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            
            // Demographics
            $table->enum('education_level', [
                'Less than high school',
                'High school diploma/GED',
                'Some college',
                'Associate degree',
                'Bachelor degree',
                'Graduate degree',
                'Unknown/not reported'
            ])->nullable();
            
            $table->enum('disability_status', [
                'Yes',
                'No',
                'Unknown/not reported'
            ])->nullable();
            
            $table->json('health_insurance_sources')->nullable();
            $table->enum('health_insurance_status', [
                'Yes',
                'No',
                'Unknown/not reported'
            ])->nullable();
            
            $table->enum('ethnicity', [
                'Hispanic or Latino',
                'Not Hispanic or Latino',
                'Unknown/not reported'
            ])->nullable();
            
            $table->json('race')->nullable();
            
            $table->enum('military_status', [
                'Currently serving',
                'Veteran',
                'Not applicable',
                'Unknown/not reported'
            ])->nullable();
            
            $table->enum('work_status', [
                'Employed full-time',
                'Employed part-time',
                'Unemployed',
                'Not in labor force',
                'Unknown/not reported'
            ])->nullable();
            
            // Financial Information
            $table->json('income_sources')->nullable();
            $table->decimal('employment_income', 10, 2)->nullable();
            $table->json('other_income_sources')->nullable();
            $table->json('non_cash_benefits')->nullable();
            
            // System fields
            $table->text('documentation_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
