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
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('household_code', 7)->unique();
            $table->enum('household_type', [
                'Single Person',
                'Two Adults NO Children',
                'Single Parent Female',
                'Single Parent Male',
                'Two Parent Household',
                'Non-related Adults with Children',
                'Multigenerational Household',
                'Other',
                'Unknown/not reported'
            ]);
            $table->integer('household_size');
            $table->enum('housing_status', [
                'Own',
                'Rent',
                'Other permanent housing',
                'Homeless',
                'Other',
                'Unknown/not reported'
            ]);
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->decimal('annual_income', 10, 2)->nullable();
            $table->decimal('hhs_poverty_percentage', 5, 2)->nullable();
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
