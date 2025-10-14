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
        if (!Schema::hasTable('property_notes')) {
            Schema::create('property_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->text('note');
            $table->enum('note_type', ['general', 'inspection', 'maintenance', 'legal', 'financial', 'other'])->default('general');
            $table->boolean('is_private')->default(false)->comment('Private notes only visible to certain roles');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('property_id');
            $table->index('note_type');
            $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_notes');
    }
};
