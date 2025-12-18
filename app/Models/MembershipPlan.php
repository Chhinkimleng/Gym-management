<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory;
    protected $casts = [
    'is_active' => 'boolean',
    ];


    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_months',
        'is_active',
    ];
}
