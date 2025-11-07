<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SageIntacctSetting Model
 *
 * Stores Sage Intacct API configuration and credentials.
 * Supports both REST API and XML Web Services authentication.
 * Only one configuration can be active at a time.
 *
 * @property int $id
 * @property string $company_id
 * @property string|null $client_id REST API Client ID
 * @property string|null $client_secret REST API Client Secret (Encrypted)
 * @property string $api_version REST API version (default: v1)
 * @property string|null $user_id
 * @property string $sender_id
 * @property string $sender_password Encrypted
 * @property string|null $web_services_user_id
 * @property string|null $web_services_password Encrypted
 * @property string|null $location_id
 * @property string|null $department_id
 * @property string|null $class_id
 * @property string $endpoint_url
 * @property string $custom_object_name
 * @property string $sync_schedule
 * @property bool $is_active
 * @property bool $code_mapping_enabled
 * @property \Carbon\Carbon|null $last_sync_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SageIntacctSetting extends Model
{
    protected $table = 'sage_intacct_settings';

    protected $fillable = [
        'company_id',
        'client_id',
        'client_secret',
        'api_version',
        'auth_method',
        'user_id',
        'sender_id',
        'sender_password',
        'web_services_user_id',
        'web_services_password',
        'location_id',
        'department_id',
        'class_id',
        'endpoint_url',
        'redirect_uri',
        'custom_object_name',
        'sync_schedule',
        'is_active',
        'last_sync_at',
        'code_mapping_enabled',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'sender_password' => 'encrypted',
        'web_services_password' => 'encrypted',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'code_mapping_enabled' => 'boolean',
    ];

    /**
     * Get the field mappings for this configuration
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(SageIntacctFieldMapping::class, 'setting_id');
    }

    /**
     * Get the sync histories for this configuration
     */
    public function syncHistories(): HasMany
    {
        return $this->hasMany(SageIntacctSyncHistory::class, 'setting_id');
    }

    /**
     * Get the currently active Sage Intacct setting
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Check if all required configuration fields are set
     * Supports both REST API and XML Web Services credentials
     */
    public function isConfigured(): bool
    {
        $authMethod = $this->auth_method ?? 'xml';

        if ($authMethod === 'oauth') {
            // Check if OAuth/REST API credentials are configured
            return ! empty($this->company_id)
                && ! empty($this->client_id)
                && ! empty($this->client_secret)
                && ! empty($this->redirect_uri);
        } else {
            // Check if XML Web Services credentials are configured
            return ! empty($this->company_id)
                && ! empty($this->sender_id)
                && ! empty($this->sender_password)
                && ! empty($this->web_services_user_id)
                && ! empty($this->web_services_password);
        }
    }

    /**
     * Activate this configuration (deactivates all others)
     */
    public function activate(): bool
    {
        // Deactivate all other settings
        self::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activate this setting
        return $this->update(['is_active' => true]);
    }

    /**
     * Update the last sync timestamp
     */
    public function updateLastSync(): bool
    {
        return $this->update(['last_sync_at' => now()]);
    }

    /**
     * Check if code mapping is enabled
     */
    public function isCodeMappingEnabled(): bool
    {
        return $this->code_mapping_enabled ?? false;
    }

    /**
     * Check if this configuration uses REST API (OAuth)
     */
    public function isRestApi(): bool
    {
        return $this->auth_method === 'oauth';
    }

    /**
     * Check if this configuration uses XML Web Services
     */
    public function isXmlApi(): bool
    {
        return $this->auth_method === 'xml';
    }

    /**
     * Check if this configuration is for sandbox environment
     */
    public function isSandbox(): bool
    {
        return strtolower($this->company_id) === 'caclv-sandbox';
    }

    /**
     * Get the appropriate endpoint URL for the API type
     */
    public function getEndpointUrl(): string
    {
        if ($this->isRestApi()) {
            return $this->endpoint_url ?? 'https://api.intacct.com';
        }

        return $this->endpoint_url ?? 'https://api.intacct.com/ia/xml/xmlgw.phtml';
    }
}
