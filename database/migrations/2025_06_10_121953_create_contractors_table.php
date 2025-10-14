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
        if (!Schema::hasTable('contractors')) {
            Schema::create('contractors', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('contact_first_name')->nullable();
                $table->string('contact_last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('fax', 20)->nullable();
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('city')->nullable();
                $table->string('state', 2)->nullable();
                $table->string('zip_code', 10)->nullable();
                $table->string('license_number')->nullable();
                $table->date('license_expiration')->nullable();
                $table->string('insurance_provider')->nullable();
                $table->string('insurance_policy_number')->nullable();
                $table->date('insurance_expiration')->nullable();
                $table->decimal('liability_coverage_amount', 12, 2)->nullable();
                $table->string('ein')->nullable()->comment('Employer Identification Number');
                $table->json('services_offered')->nullable()->comment('Array of services contractor offers');
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();

                $table->index('company_name');
                $table->index('status');
                $table->index('license_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
