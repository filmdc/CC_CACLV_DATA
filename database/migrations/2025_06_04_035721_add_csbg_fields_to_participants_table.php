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
        Schema::table('participants', function (Blueprint $table) {
            // Employment details for CSBG reporting
            $table->decimal('hours_worked_per_week', 5, 2)->nullable()->after('work_status');
            $table->boolean('is_seeking_employment')->nullable()->after('hours_worked_per_week');
            $table->boolean('is_underemployed')->nullable()->after('is_seeking_employment');
            
            // Education status
            $table->boolean('currently_enrolled_in_education')->nullable()->after('education_level');
            $table->string('education_enrollment_type')->nullable()->after('currently_enrolled_in_education'); // K-12, GED, College, etc.
            
            // Health/Disability details
            $table->json('disability_types')->nullable()->after('disability_status'); // Multiple disability types
            $table->json('assistance_needs')->nullable()->after('disability_types'); // Types of assistance needed
            $table->boolean('has_chronic_health_conditions')->nullable()->after('assistance_needs');
            
            // Service tracking
            $table->date('intake_date')->nullable()->after('notes');
            $table->date('last_service_date')->nullable()->after('intake_date');
            $table->boolean('is_disconnected_youth')->nullable()->after('last_service_date'); // Computed field helper
            
            // CSBG specific tracking
            $table->string('csbg_age_group')->nullable()->after('is_disconnected_youth'); // Computed field
            $table->string('csbg_income_classification')->nullable()->after('csbg_age_group'); // Computed field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn([
                'hours_worked_per_week',
                'is_seeking_employment', 
                'is_underemployed',
                'currently_enrolled_in_education',
                'education_enrollment_type',
                'disability_types',
                'assistance_needs',
                'has_chronic_health_conditions',
                'intake_date',
                'last_service_date',
                'is_disconnected_youth',
                'csbg_age_group',
                'csbg_income_classification'
            ]);
        });
    }
};
