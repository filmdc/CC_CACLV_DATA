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
            // Check if columns don't already exist before adding them
            if (!Schema::hasColumn('sage_intacct_settings', 'oauth_client_id')) {
                // OAuth credentials
                $table->string('oauth_client_id')->nullable()->after('web_services_password');
                $table->text('oauth_client_secret')->nullable()->after('oauth_client_id'); // Encrypted
                $table->string('oauth_redirect_uri')->nullable()->after('oauth_client_secret');

                // OAuth tokens
                $table->text('oauth_access_token')->nullable()->after('oauth_redirect_uri'); // Encrypted
                $table->text('oauth_refresh_token')->nullable()->after('oauth_access_token'); // Encrypted
                $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_refresh_token');
                $table->timestamp('oauth_connected_at')->nullable()->after('oauth_token_expires_at');

                // Authentication method: 'credentials' (XML Web Services) or 'oauth'
                $table->enum('auth_method', ['credentials', 'oauth'])->default('credentials')->after('oauth_connected_at');

                // Index for token expiration checks
                $table->index('oauth_token_expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropIndex(['oauth_token_expires_at']);

            $table->dropColumn([
                'oauth_client_id',
                'oauth_client_secret',
                'oauth_redirect_uri',
                'oauth_access_token',
                'oauth_refresh_token',
                'oauth_token_expires_at',
                'oauth_connected_at',
                'auth_method',
            ]);
        });
    }
};
