<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function defaults(): array
    {
        return [
            'gym_name' => 'PulseCore Fitness',
            'gym_tagline' => 'Strength, Conditioning & Everyday Performance',
            'gym_email' => 'hello@pulsecore.gym',
            'gym_phone' => '+1 (555) 234-8910',
            'gym_address' => '740 Mercer St, Suite 200, Portland, OR',
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'membership_overlap_policy' => 'prevent_overlap', // prevent_overlap | queue_after_active | allow_multiple
            'expiring_notice_days' => '7',
            'payment_methods' => json_encode(['cash', 'bank_transfer', 'card', 'mobile_money']),
            'receipt_footer' => 'Thank you for training with us. Keep pushing forward!',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (!Schema::hasTable('settings')) {
                return $default ?? (self::defaults()[$key] ?? null);
            }

            $all = Cache::remember('app_settings_map', 300, function () {
                return self::query()->pluck('value', 'key')->toArray();
            });

            return $all[$key] ?? $default ?? (self::defaults()[$key] ?? null);
        } catch (\Throwable) {
            return $default ?? (self::defaults()[$key] ?? null);
        }
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]
        );

        Cache::forget('app_settings_map');
    }

    public static function paymentMethods(): array
    {
        $raw = self::getValue('payment_methods');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded) || empty($decoded)) {
            return ['cash', 'bank_transfer', 'card'];
        }

        return $decoded;
    }

    public static function currencySymbol(): string
    {
        return (string) self::getValue('currency_symbol', '$');
    }
}
