<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Monthly',
                'description' => 'Full gym floor access, locker rooms, and baseline fitness assessment.',
                'billing_cycle' => MembershipPlan::CYCLE_MONTHLY,
                'price' => 59.00,
                'duration_months' => 1,
                'duration_days' => null,
                'benefits' => ['24/7 Gym Floor Access', 'Locker & Shower Access', '1 Onboarding Assessment'],
                'is_active' => true,
            ],
            [
                'name' => 'Performance Quarterly',
                'description' => 'Three months of strength training, group conditioning classes, and coach check-ins.',
                'billing_cycle' => MembershipPlan::CYCLE_QUARTERLY,
                'price' => 159.00,
                'duration_months' => 3,
                'duration_days' => null,
                'benefits' => ['24/7 Gym Floor Access', 'Unlimited Group Classes', 'Monthly Body Composition Scan'],
                'is_active' => true,
            ],
            [
                'name' => 'Momentum Semi-Annual',
                'description' => 'Six months of all-access coaching, recovery lounge, and nutrition guidance.',
                'billing_cycle' => MembershipPlan::CYCLE_SEMI_ANNUAL,
                'price' => 299.00,
                'duration_months' => 6,
                'duration_days' => null,
                'benefits' => ['All-Access Floor & Classes', 'Recovery Zone & Sauna', '2 Personal Training Sessions'],
                'is_active' => true,
            ],
            [
                'name' => 'Elite Annual',
                'description' => 'Full year unlimited membership with priority coaching and guest passes.',
                'billing_cycle' => MembershipPlan::CYCLE_ANNUAL,
                'price' => 540.00,
                'duration_months' => 12,
                'duration_days' => null,
                'benefits' => ['Unlimited Everything', '4 Guest Passes / Month', 'Dedicated Locker', 'Quarterly Program Review'],
                'is_active' => true,
            ],
            [
                'name' => '14-Day Sprint Pass',
                'description' => 'Short-term custom pass for travelers or trial athletes.',
                'billing_cycle' => MembershipPlan::CYCLE_CUSTOM,
                'price' => 29.00,
                'duration_months' => null,
                'duration_days' => 14,
                'benefits' => ['14 Consecutive Days Access', 'Group Class Drop-In'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
