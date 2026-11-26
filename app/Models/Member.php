<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'membership_id',
        'name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'photo',
        'joining_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('start_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('check_in');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->latest('end_date');
    }

    public function activeAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)
            ->whereNull('check_out')
            ->latest('check_in');
    }

    public function isEligibleForCheckIn(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->relationLoaded('activeSubscription')) {
            return $this->activeSubscription !== null;
        }

        return $this->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();
    }

    public function outstandingBalance(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
                ->sum('remaining_balance');
        }

        return (float) $this->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
            ->sum('remaining_balance');
    }

    public static function generateUniqueMembershipId(): string
    {
        do {
            $id = 'GYM-' . now()->format('ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (self::withTrashed()->where('membership_id', $id)->exists());

        return $id;
    }
}
