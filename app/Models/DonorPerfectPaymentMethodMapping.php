<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonorPerfectPaymentMethodMapping extends Model
{
    protected $fillable = [
        'dp_payment_type',
        'intacct_payment_method',
        'is_default',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get Sage Intacct payment method for given DonorPerfect payment type
     *
     * @param  string|null  $dpPaymentType  DonorPerfect PAYMENT_TYPE value
     * @return string Sage Intacct payment method ('Printed Check', 'Cash', 'EFT', 'Credit Card')
     */
    public static function getIntacctPaymentMethod(?string $dpPaymentType): string
    {
        // Try exact match first
        if ($dpPaymentType) {
            $mapping = self::where('dp_payment_type', $dpPaymentType)
                ->where('is_active', true)
                ->orderBy('order')
                ->first();

            if ($mapping) {
                return $mapping->intacct_payment_method;
            }

            // Try case-insensitive partial match
            $normalized = strtoupper(trim($dpPaymentType));
            $mapping = self::whereRaw('UPPER(dp_payment_type) LIKE ?', ["%{$normalized}%"])
                ->where('is_active', true)
                ->orderBy('order')
                ->first();

            if ($mapping) {
                return $mapping->intacct_payment_method;
            }
        }

        // Fall back to default mapping
        $defaultMapping = self::where('is_default', true)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        return $defaultMapping->intacct_payment_method ?? 'Cash';
    }
}
