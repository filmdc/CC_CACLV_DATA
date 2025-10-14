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
        if (!Schema::hasTable('program_manager_uploads')) {
            Schema::create('program_manager_uploads', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['general', 'hmis', 'rx_office']);
            $table->string('program_manager_name');
            $table->string('program_manager_email');
            $table->string('organization')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size');
            $table->string('ip_address')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_manager_uploads');
    }
};
