<?php

// database/seeders/MembershipPlanSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MembershipPlan;

class MembershipPlanSeeder extends Seeder
{
    public function run()
    {
        MembershipPlan::create([
            'name' => 'Monthly Plan',
            'description' => 'Basic monthly membership',
            'price' => 50.00,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        MembershipPlan::create([
            'name' => 'Quarterly Plan',
            'description' => '3 months membership with discount',
            'price' => 135.00,
            'duration_months' => 3,
            'is_active' => true,
        ]);

        MembershipPlan::create([
            'name' => 'Annual Plan',
            'description' => 'Full year membership with best discount',
            'price' => 480.00,
            'duration_months' => 12,
            'is_active' => true,
        ]);
    }
}
