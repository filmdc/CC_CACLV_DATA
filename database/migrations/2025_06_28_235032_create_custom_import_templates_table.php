<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_import_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['preconfigured', 'custom'])->default('custom');
            $table->string('source_system')->nullable(); // e.g., 'HMIS_Pennsylvania', 'Custom'
            $table->boolean('active')->default(true);
            $table->json('field_mappings'); // Maps CSV columns to our database fields
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->json('validation_rules')->nullable(); // Custom validation rules
            $table->json('import_settings')->nullable(); // Additional settings like default program, etc.
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['type', 'active']);
            $table->index(['source_system', 'active']);
        });

        Schema::create('custom_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('custom_import_templates');
            $table->foreignId('user_id')->constrained('users');
            $table->string('original_filename');
            $table->string('status'); // 'success', 'partial', 'failed'
            $table->integer('total_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['template_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_import_logs');
        Schema::dropIfExists('custom_import_templates');
    }
};