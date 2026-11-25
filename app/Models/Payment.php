<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payment_reference',
        'member_id',
        'subscription_id',
        'total_amount',
        'amount_paid',
        'remaining_balance',
        'refunded_amount',
        'refunded_at',
        'refund_reason',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'refunded_at' => 'datetime',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function netAmount(): float
    {
        return max(0, round((float) $this->amount_paid - (float) $this->refunded_amount, 2));
    }

    public function isRefundable(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_PARTIALLY_PAID], true)
            && $this->netAmount() > 0;
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (self::withTrashed()->where('payment_reference', $ref)->exists());

        return $ref;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }
}
