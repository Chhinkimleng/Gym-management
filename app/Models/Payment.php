<?php
// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'subscription_id', 'amount', 'payment_date',
        'payment_method', 'transaction_id', 'status', 'notes'
    ];

    protected $casts = [
    'payment_date' => 'datetime',
    'amount' => 'decimal:2', // ensures 2 decimal places
];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}