<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('text'); // text, file, boolean, etc.
                $table->timestamps();
            });
        }

        $defaults = [
            ['key' => 'site_name', 'value' => 'CAP TRACK', 'type' => 'text'],
            ['key' => 'site_logo', 'value' => null, 'type' => 'file'],
            ['key' => 'site_favicon', 'value' => null, 'type' => 'file'],
        ];

        foreach ($defaults as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
