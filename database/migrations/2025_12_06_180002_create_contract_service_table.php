<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This pivot table links services to their funding contracts.
     * A service can be funded by multiple contracts, and a contract can fund multiple services.
     */
    public function up(): void
    {
        Schema::create('contract_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('allocation_percentage', 5, 2)->nullable()->comment('Percentage of contract allocated to this service');
            $table->decimal('allocated_amount', 15, 2)->nullable()->comment('Fixed dollar amount allocated to this service');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Ensure unique combination of contract and service
            $table->unique(['contract_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_service');
    }
};
