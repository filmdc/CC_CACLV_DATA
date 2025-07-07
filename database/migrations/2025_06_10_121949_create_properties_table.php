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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('household_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('property_type_id')->nullable()->comment('Reference to reference_data for property types');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->string('county')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->year('year_built')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->decimal('bathrooms', 3, 1)->nullable();
            $table->integer('square_footage')->nullable();
            $table->text('property_description')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['participant_id', 'household_id']);
            $table->index('property_type_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
