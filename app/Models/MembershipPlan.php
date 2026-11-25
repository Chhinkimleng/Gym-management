<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPlan extends Model
{
    use HasFactory, SoftDeletes;

    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_QUARTERLY = 'quarterly';
    public const CYCLE_SEMI_ANNUAL = 'semi_annual';
    public const CYCLE_ANNUAL = 'annual';
    public const CYCLE_CUSTOM = 'custom';

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'duration_months' => 'integer',
        'duration_days' => 'integer',
        'benefits' => 'array',
    ];

    protected $fillable = [
        'name',
        'description',
        'billing_cycle',
        'price',
        'duration_months',
        'duration_days',
        'benefits',
        'is_active',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', now()->toDateString());
    }

    public function canBeDeleted(): bool
    {
        return !$this->subscriptions()->withTrashed()->exists();
    }

    public function formattedDuration(): string
    {
        if ($this->billing_cycle === self::CYCLE_CUSTOM && $this->duration_days) {
            return $this->duration_days . ' ' . ($this->duration_days === 1 ? 'day' : 'days');
        }

        $months = $this->duration_months ?? 1;
        return $months . ' ' . ($months === 1 ? 'month' : 'months');
    }

    public static function billingCycles(): array
    {
        return [
            self::CYCLE_MONTHLY => 'Monthly (1 Month)',
            self::CYCLE_QUARTERLY => 'Quarterly (3 Months)',
            self::CYCLE_SEMI_ANNUAL => 'Semi-Annual (6 Months)',
            self::CYCLE_ANNUAL => 'Annual (12 Months)',
            self::CYCLE_CUSTOM => 'Custom Duration',
        ];
    }
}
