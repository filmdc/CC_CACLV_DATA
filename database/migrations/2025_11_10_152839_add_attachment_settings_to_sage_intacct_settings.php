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
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            // Add attachment/check image sync settings
            $table->boolean('include_check_images')->default(false)->after('code_mapping_enabled');
            $table->string('attachment_folder_name', 100)->default('Gift_Check_Images')->after('include_check_images');
            $table->integer('attachment_max_size_mb')->default(10)->after('attachment_folder_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn(['include_check_images', 'attachment_folder_name', 'attachment_max_size_mb']);
        });
    }
};
