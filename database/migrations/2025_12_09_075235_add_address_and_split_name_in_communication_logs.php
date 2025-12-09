<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            // Add new name fields
            $table->string('contact_first_name')->nullable()->after('direction');
            $table->string('contact_last_name')->nullable()->after('contact_first_name');

            // Add address fields
            $table->string('contact_address')->nullable()->after('contact_email');
            $table->string('contact_city')->nullable()->after('contact_address');
            $table->string('contact_state', 2)->nullable()->after('contact_city');
            $table->string('contact_zip', 10)->nullable()->after('contact_state');
        });

        // Migrate existing data - split contact_name into first and last name
        DB::table('communication_logs')->whereNotNull('contact_name')->orderBy('id')->chunk(100, function ($logs) {
            foreach ($logs as $log) {
                $nameParts = explode(' ', trim($log->contact_name), 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';

                // Check for "Last, First" format
                if (str_contains($log->contact_name, ',')) {
                    $commaParts = explode(',', $log->contact_name, 2);
                    $lastName = trim($commaParts[0] ?? '');
                    $firstName = trim($commaParts[1] ?? '');
                }

                DB::table('communication_logs')
                    ->where('id', $log->id)
                    ->update([
                        'contact_first_name' => $firstName ?: null,
                        'contact_last_name' => $lastName ?: null,
                    ]);
            }
        });

        // Drop old contact_name column
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropColumn('contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            // Re-add contact_name field
            $table->string('contact_name')->after('direction');
        });

        // Migrate data back - combine first and last name
        DB::table('communication_logs')->orderBy('id')->chunk(100, function ($logs) {
            foreach ($logs as $log) {
                $fullName = trim(($log->contact_first_name ?? '').' '.($log->contact_last_name ?? ''));

                DB::table('communication_logs')
                    ->where('id', $log->id)
                    ->update([
                        'contact_name' => $fullName ?: 'Unknown',
                    ]);
            }
        });

        // Drop new fields
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropColumn([
                'contact_first_name',
                'contact_last_name',
                'contact_address',
                'contact_city',
                'contact_state',
                'contact_zip',
            ]);
        });
    }
};
