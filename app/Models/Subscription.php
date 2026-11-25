<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'member_id',
        'membership_plan_id',
        'plan_name',
        'plan_price',
        'duration_months',
        'duration_days',
        'start_date',
        'end_date',
        'status',
        'notes',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'plan_price' => 'decimal:2',
        'duration_months' => 'integer',
        'duration_days' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function effectivePlanName(): string
    {
        return $this->plan_name ?? $this->membershipPlan?->name ?? 'Archived Plan';
    }

    public function effectivePrice(): float
    {
        if ($this->plan_price !== null) {
            return (float) $this->plan_price;
        }

        return (float) ($this->membershipPlan?->price ?? 0);
    }

    public function effectiveDurationLabel(): string
    {
        if ($this->duration_days) {
            return $this->duration_days . ' ' . ($this->duration_days === 1 ? 'day' : 'days');
        }

        $months = $this->duration_months ?? $this->membershipPlan?->duration_months ?? 1;
        return $months . ' ' . ($months === 1 ? 'month' : 'months');
    }

    public function netPaidAmount(): float
    {
        $paid = (float) $this->payments()
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_PAID, Payment::STATUS_REFUNDED])
            ->sum('amount_paid');
        $refunded = (float) $this->payments()->sum('refunded_amount');

        return max(0, round($paid - $refunded, 2));
    }

    public function remainingBalance(): float
    {
        return max(0, round($this->effectivePrice() - $this->netPaidAmount(), 2));
    }

    public function computedStatus(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }
        if ($this->status === self::STATUS_PENDING && $this->start_date->isFuture()) {
            return self::STATUS_PENDING;
        }
        if ($this->end_date->lt(now()->startOfDay())) {
            return self::STATUS_EXPIRED;
        }
        return $this->status;
    }

    public function daysRemaining(): int
    {
        if ($this->computedStatus() !== self::STATUS_ACTIVE) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay(), false));
    }

    public static function syncExpiredStatuses(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => self::STATUS_EXPIRED]);
    }
}
