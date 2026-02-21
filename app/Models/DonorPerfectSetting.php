<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonorPerfectSetting extends Model
{
    protected $fillable = [
        'api_url',
        'api_key',
        'api_login',
        'api_password',
        'selected_fields',
        'is_active',
        'cache_duration',
        'last_connection_test',
        'connection_status',
        'connection_error_message',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_login' => 'encrypted',
        'api_password' => 'encrypted',
        'selected_fields' => 'array',
        'is_active' => 'boolean',
        'cache_duration' => 'integer',
        'last_connection_test' => 'datetime',
    ];

    /**
     * Default fields to retrieve from DonorPerfect if none are configured.
     * Includes gift, donor, and UDF fields needed by the gifts feed and batch sync.
     */
    protected const DEFAULT_FIELDS = [
        'gift_id',
        'donor_id',
        'gift_date',
        'amount',
        'gl_code',
        'solicit_code',
        'sub_solicit_code',
        'campaign',
        'gift_type',
        'batch_no',
        'first_name',
        'last_name',
        'email',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'PAYMENT_TYPE',
        'DP_CheckScan_Batch',
        'IMAGE_CHECK',
        'BATCH',
    ];

    /**
     * Get the active DonorPerfect configuration.
     * Returns the first active configuration or null if none exists.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Check if API credentials are configured.
     * Returns true if all required API credentials exist.
     */
    public function isConfigured(): bool
    {
        try {
            return ! empty($this->api_url)
                && ! empty($this->api_key)
                && ! empty($this->api_login)
                && ! empty($this->api_password);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return false;
        }
    }

    /**
     * Get the selected fields array or default fields if none configured.
     * Returns array of field names to retrieve from DonorPerfect API.
     */
    public function getSelectedFields(): array
    {
        if (! empty($this->selected_fields) && is_array($this->selected_fields)) {
            return $this->selected_fields;
        }

        return self::DEFAULT_FIELDS;
    }
}
