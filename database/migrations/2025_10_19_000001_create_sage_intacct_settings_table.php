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
        Schema::create('sage_intacct_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('sender_id');
            $table->text('sender_password'); // Encrypted
            $table->string('web_services_user_id');
            $table->text('web_services_password'); // Encrypted
            $table->string('endpoint_url')->default('https://api.intacct.com/ia/xml/xmlgw.phtml');
            $table->string('custom_object_name')->default('Donor Perfect Gifts');
            $table->string('sync_schedule')->nullable(); // Cron expression
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Index for performance
            $table->index('is_active');
            $table->index('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sage_intacct_settings');
    }
};
