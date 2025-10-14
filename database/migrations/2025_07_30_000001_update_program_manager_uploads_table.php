<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_manager_uploads', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'rejected'])->default('pending')->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->json('processing_result')->nullable()->after('review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('program_manager_uploads', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'reviewed_by', 'reviewed_at', 'review_notes', 'processing_result']);
        });
    }
};
