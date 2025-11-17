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
        Schema::create('campaign_service_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_code')->comment('DonorPerfect campaign code (e.g., P1400)');
            $table->string('intacct_location_code')->comment('Mapped Sage Intacct location code');
            $table->string('service_code')->comment('Service (classid) to use for this campaign (e.g., S14999)');
            $table->string('project_code')->nullable()->comment('Optional: Project (projectid) to use (e.g., F8000)');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure unique campaign codes
            $table->unique('campaign_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_service_overrides');
    }
};
