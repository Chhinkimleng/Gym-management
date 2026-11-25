<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Setting;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MembershipService
{
    public function calculateEndDate(MembershipPlan $plan, Carbon $startDate): Carbon
    {
        if ($plan->billing_cycle === MembershipPlan::CYCLE_CUSTOM && $plan->duration_days) {
            return $startDate->copy()->addDays((int) $plan->duration_days);
        }

        $months = max(1, (int) ($plan->duration_months ?? 1));
        return $startDate->copy()->addMonthsNoOverflow($months);
    }

    public function createSubscription(
        Member $member,
        MembershipPlan $plan,
        Carbon $startDate,
        ?string $requestedStatus = null,
        ?string $notes = null,
        bool $isRenewal = false
    ): Subscription {
        if (!$plan->is_active) {
            throw new InvalidArgumentException('Cannot assign an inactive membership plan.');
        }

        return DB::transaction(function () use ($member, $plan, $startDate, $requestedStatus, $notes, $isRenewal) {
            $lockedMember = Member::lockForUpdate()->findOrFail($member->id);

            $policy = Setting::getValue('membership_overlap_policy', 'prevent_overlap');

            $activeOrPending = Subscription::where('member_id', $lockedMember->id)
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderByDesc('end_date')
                ->get();

            $effectiveStart = $startDate->copy()->startOfDay();
            $effectiveEnd = $this->calculateEndDate($plan, $effectiveStart);

            if ($isRenewal && $activeOrPending->isNotEmpty()) {
                $latestEnd = $activeOrPending->max('end_date');
                if ($latestEnd && $effectiveStart->lte($latestEnd)) {
                    $effectiveStart = Carbon::parse($latestEnd)->addDay()->startOfDay();
                    $effectiveEnd = $this->calculateEndDate($plan, $effectiveStart);
                }
            } elseif ($policy !== 'allow_multiple' && $activeOrPending->isNotEmpty()) {
                $overlapping = $activeOrPending->first(function (Subscription $sub) use ($effectiveStart, $effectiveEnd) {
                    return $sub->start_date->lte($effectiveEnd) && $sub->end_date->gte($effectiveStart);
                });

                if ($overlapping) {
                    if ($policy === 'queue_after_active') {
                        $effectiveStart = $overlapping->end_date->copy()->addDay()->startOfDay();
                        $effectiveEnd = $this->calculateEndDate($plan, $effectiveStart);
                    } else {
                        throw new InvalidArgumentException(
                            "Member already has an active/pending membership ({$overlapping->effectivePlanName()}) from {$overlapping->start_date->format('M d, Y')} to {$overlapping->end_date->format('M d, Y')}. Use Renew instead."
                        );
                    }
                }
            }

            $status = $this->resolveStatus($effectiveStart, $effectiveEnd, $requestedStatus);

            $subscription = Subscription::create([
                'member_id' => $lockedMember->id,
                'membership_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_price' => $plan->price,
                'duration_months' => $plan->duration_months,
                'duration_days' => $plan->duration_days,
                'start_date' => $effectiveStart->toDateString(),
                'end_date' => $effectiveEnd->toDateString(),
                'status' => $status,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            if ($lockedMember->status !== Member::STATUS_ACTIVE && $status === Subscription::STATUS_ACTIVE) {
                $lockedMember->update(['status' => Member::STATUS_ACTIVE]);
            }

            ActivityLog::record(
                $isRenewal ? 'membership.renewed' : 'membership.created',
                ($isRenewal ? 'Renewed' : 'Assigned') . " {$plan->name} membership for {$lockedMember->name} ({$effectiveStart->format('M d, Y')} - {$effectiveEnd->format('M d, Y')}).",
                $subscription,
                ['member_id' => $lockedMember->id, 'plan_id' => $plan->id, 'price' => (float) $plan->price]
            );

            return $subscription;
        });
    }

    public function cancelSubscription(Subscription $subscription, ?string $reason = null): Subscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $locked = Subscription::lockForUpdate()->findOrFail($subscription->id);

            if ($locked->status === Subscription::STATUS_CANCELLED) {
                throw new InvalidArgumentException('Membership is already cancelled.');
            }

            $notes = $locked->notes;
            if ($reason) {
                $notes = trim(($notes ? $notes . "\n" : '') . 'Cancelled: ' . $reason);
            }

            $locked->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes' => $notes,
            ]);

            ActivityLog::record(
                'membership.cancelled',
                "Cancelled {$locked->effectivePlanName()} membership (#{$locked->id}) for {$locked->member?->name}.",
                $locked,
                ['reason' => $reason]
            );

            return $locked;
        });
    }

    private function resolveStatus(Carbon $startDate, Carbon $endDate, ?string $requestedStatus): string
    {
        if ($requestedStatus === Subscription::STATUS_CANCELLED) {
            return Subscription::STATUS_CANCELLED;
        }
        if ($requestedStatus === Subscription::STATUS_PENDING || $startDate->gt(now()->startOfDay())) {
            return Subscription::STATUS_PENDING;
        }
        if ($endDate->lt(now()->startOfDay())) {
            return Subscription::STATUS_EXPIRED;
        }

        return Subscription::STATUS_ACTIVE;
    }
}
