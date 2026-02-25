<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_type_id')->nullable()->constrained('approval_types')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('submitter_notes')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('status', 50)->default('draft');
            $table->foreignId('submitted_by')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['approval_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_submissions');
    }
};
