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
        DB::statement("ALTER TABLE milestone_documents MODIFY COLUMN status ENUM('not_uploaded', 'uploaded', 'pending_review', 'approved', 'rejected') NOT NULL DEFAULT 'not_uploaded'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE milestone_documents MODIFY COLUMN status ENUM('not_uploaded', 'pending_review', 'approved', 'rejected') NOT NULL DEFAULT 'not_uploaded'");
    }
};
