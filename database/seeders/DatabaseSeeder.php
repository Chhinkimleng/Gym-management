<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::defaults() as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => 'general']);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Alex Rivera (Owner)',
                'role' => User::ROLE_ADMIN,
                'phone' => '+1 555-0101',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        $staff = User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Jordan Lee (Front Desk)',
                'role' => User::ROLE_STAFF,
                'phone' => '+1 555-0102',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        $this->call(MembershipPlanSeeder::class);

        $plans = MembershipPlan::all()->keyBy('name');

        if (Member::count() === 0) {
            $memberSeedData = [
                ['Maya Lin', 'maya.lin@example.com', '+1 555-2101', 'female', '1995-04-12', 'active', 'Elite Annual', -65],
                ['Marcus Vance', 'marcus.vance@example.com', '+1 555-2102', 'male', '1991-09-23', 'active', 'Performance Quarterly', -20],
                ['Elena Rostova', 'elena.rostova@example.com', '+1 555-2103', 'female', '1997-01-08', 'active', 'Starter Monthly', -26],
                ['Devon Brooks', 'devon.brooks@example.com', '+1 555-2104', 'male', '1988-11-19', 'active', 'Momentum Semi-Annual', -45],
                ['Sora Takahashi', 'sora.t@example.com', '+1 555-2105', 'other', '1999-06-30', 'active', 'Starter Monthly', -25],
                ['Liam OConnor', 'liam.oconnor@example.com', '+1 555-2106', 'male', '1993-02-14', 'active', 'Performance Quarterly', -12],
                ['Nadia Patel', 'nadia.patel@example.com', '+1 555-2107', 'female', '1996-08-03', 'active', 'Elite Annual', -10],
                ['Carlos Mendez', 'carlos.m@example.com', '+1 555-2108', 'male', '1986-05-27', 'inactive', 'Starter Monthly', -70],
                ['Chloe Mercer', 'chloe.m@example.com', '+1 555-2109', 'female', '2000-12-01', 'active', '14-Day Sprint Pass', -4],
                ['Tariq Al-Mansoor', 'tariq.a@example.com', '+1 555-2110', 'male', '1994-03-17', 'active', 'Performance Quarterly', -5],
            ];

            foreach ($memberSeedData as $idx => $row) {
                [$name, $email, $phone, $gender, $dob, $status, $planName, $offsetDays] = $row;
                $joinDate = now()->copy()->addDays($offsetDays);
                $member = Member::create([
                    'membership_id' => sprintf('GYM-%s-%04d', now()->format('ym'), $idx + 1),
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'date_of_birth' => $dob,
                    'gender' => $gender,
                    'address' => ($idx + 101) . ' Hawthorne Blvd, Portland, OR',
                    'emergency_contact_name' => 'Emergency Contact ' . ($idx + 1),
                    'emergency_contact_phone' => '+1 555-90' . str_pad((string) ($idx + 10), 2, '0', STR_PAD_LEFT),
                    'joining_date' => $joinDate->toDateString(),
                    'status' => $status,
                    'created_by' => $idx % 2 === 0 ? $admin->id : $staff->id,
                ]);

                $plan = $plans[$planName] ?? $plans->first();
                $startDate = $joinDate->copy();
                $endDate = $plan->duration_days
                    ? $startDate->copy()->addDays($plan->duration_days)
                    : $startDate->copy()->addMonthsNoOverflow($plan->duration_months ?? 1);

                $subStatus = $endDate->lt(now()->startOfDay())
                    ? Subscription::STATUS_EXPIRED
                    : Subscription::STATUS_ACTIVE;

                $sub = Subscription::create([
                    'member_id' => $member->id,
                    'membership_plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'plan_price' => $plan->price,
                    'duration_months' => $plan->duration_months,
                    'duration_days' => $plan->duration_days,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'status' => $subStatus,
                    'created_by' => $admin->id,
                ]);

                $isPartial = $idx === 5;
                $paidAmount = $isPartial ? round((float) $plan->price * 0.5, 2) : (float) $plan->price;
                $remaining = round((float) $plan->price - $paidAmount, 2);

                Payment::create([
                    'payment_reference' => 'PAY-' . $startDate->format('Ymd') . '-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                    'member_id' => $member->id,
                    'subscription_id' => $sub->id,
                    'total_amount' => $plan->price,
                    'amount_paid' => $paidAmount,
                    'remaining_balance' => $remaining,
                    'amount' => $paidAmount,
                    'payment_date' => $startDate->copy()->setTime(10 + ($idx % 7), 15),
                    'payment_method' => ['card', 'cash', 'bank_transfer'][$idx % 3],
                    'transaction_id' => $idx % 3 === 0 ? 'TXN-' . strtoupper(substr(md5($email), 0, 8)) : null,
                    'status' => $isPartial ? Payment::STATUS_PARTIALLY_PAID : Payment::STATUS_PAID,
                    'recorded_by' => $idx % 2 === 0 ? $admin->id : $staff->id,
                ]);

                if ($status === 'active') {
                    // Historical attendance over last 6 days
                    for ($d = 5; $d >= 1; $d--) {
                        if (($idx + $d) % 2 === 0) {
                            $in = now()->copy()->subDays($d)->setTime(7 + ($idx % 10), 15);
                            Attendance::create([
                                'member_id' => $member->id,
                                'check_in' => $in,
                                'check_out' => $in->copy()->addMinutes(55 + ($idx * 5)),
                                'status' => Attendance::STATUS_CHECKED_OUT,
                                'recorded_by' => $staff->id,
                            ]);
                        }
                    }

                    // Today's check-ins for first 4 active members (2 still in gym, 2 checked out)
                    if ($idx < 4) {
                        $inToday = now()->copy()->subHours(2 + $idx);
                        $checkedOut = $idx >= 2;
                        Attendance::create([
                            'member_id' => $member->id,
                            'check_in' => $inToday,
                            'check_out' => $checkedOut ? $inToday->copy()->addMinutes(65) : null,
                            'status' => $checkedOut ? Attendance::STATUS_CHECKED_OUT : Attendance::STATUS_CHECKED_IN,
                            'recorded_by' => $staff->id,
                        ]);
                    }
                }
            }
        }
    }
}
