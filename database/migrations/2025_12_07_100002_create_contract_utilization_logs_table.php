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
        Schema::create('contract_utilization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // budget_created, budget_updated, expense_created, expense_updated, expense_deleted, admin_override
            $table->string('model_type'); // ProjectBudget | ProjectExpense
            $table->unsignedBigInteger('model_id');
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2)->nullable();
            $table->decimal('contract_utilization_before', 10, 2);
            $table->decimal('contract_utilization_after', 10, 2);
            $table->decimal('contract_remaining_before', 15, 2);
            $table->decimal('contract_remaining_after', 15, 2);
            $table->boolean('was_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_utilization_logs');
    }
};
