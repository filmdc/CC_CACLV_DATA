<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_code_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_code_id')
                ->constrained('fund_codes')
                ->cascadeOnDelete();
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->boolean('is_hard_requirement')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['fund_code_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_code_requirements');
    }
};
