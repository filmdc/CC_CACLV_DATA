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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('contract_number')->nullable();
            $table->string('cfda_number')->nullable();
            $table->text('purpose')->nullable();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->string('funder')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('documents')->nullable();
            $table->text('notes')->nullable();
            $table->string('fund_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
