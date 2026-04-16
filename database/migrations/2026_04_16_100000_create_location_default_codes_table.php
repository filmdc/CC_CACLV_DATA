<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_default_codes', function (Blueprint $table) {
            $table->id();
            $table->string('intacct_location_code');
            $table->string('field_type'); // solicit_code, sub_solicit_code
            $table->string('default_intacct_code');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['intacct_location_code', 'field_type'], 'location_field_type_unique');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_default_codes');
    }
};
