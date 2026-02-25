<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_chains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_type_id')->constrained('approval_types')->cascadeOnDelete();
            $table->tinyInteger('step_number')->unsigned();
            $table->string('step_name', 100);
            $table->string('required_permission', 150)->nullable();
            $table->string('required_role', 100)->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['approval_type_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_chains');
    }
};
