<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function buildReportData(Carbon $from, Carbon $to, ?string $search = null): array
    {
        Subscription::syncExpiredStatuses();

        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->endOfDay();

        $paymentsQuery = Payment::with([
            'member:id,name,membership_id',
            'subscription:id,membership_plan_id,plan_name,plan_price',
            'recorder:id,name',
        ])
            ->select(['id', 'payment_reference', 'member_id', 'subscription_id', 'total_amount', 'amount_paid', 'remaining_balance', 'refunded_amount', 'payment_method', 'status', 'payment_date', 'recorded_by'])
            ->whereBetween('payment_date', [$fromDate, $toDate]);

        $subscriptionsQuery = Subscription::with([
            'member:id,name,membership_id',
        ])
            ->select(['id', 'member_id', 'membership_plan_id', 'plan_name', 'plan_price', 'start_date', 'end_date', 'status'])
            ->whereBetween('start_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $membersQuery = Member::with('activeSubscription:id,member_id,membership_plan_id,plan_name,end_date')
            ->select(['id', 'membership_id', 'name', 'email', 'phone', 'gender', 'joining_date', 'status'])
            ->whereBetween('joining_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $attendanceQuery = Attendance::with([
            'member:id,name,membership_id',
            'recorder:id,name',
        ])
            ->select(['id', 'member_id', 'check_in', 'check_out', 'status', 'recorded_by'])
            ->whereBetween('check_in', [$fromDate, $toDate]);

        if ($search) {
            $term = '%' . $search . '%';
            $paymentsQuery->where(function ($q) use ($term) {
                $q->where('payment_reference', 'like', $term)
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', $term)->orWhere('membership_id', 'like', $term));
            });
            $subscriptionsQuery->whereHas('member', fn ($mq) => $mq->where('name', 'like', $term)->orWhere('membership_id', 'like', $term));
            $membersQuery->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('membership_id', 'like', $term));
            $attendanceQuery->whereHas('member', fn ($mq) => $mq->where('name', 'like', $term)->orWhere('membership_id', 'like', $term));
        }

        $payments = $paymentsQuery->latest('payment_date')->get();
        $subscriptions = $subscriptionsQuery->latest('start_date')->get();
        $newMembers = $membersQuery->latest('joining_date')->get();
        $attendances = $attendanceQuery->latest('check_in')->get();

        $grossCollected = (float) $payments->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_PAID, Payment::STATUS_REFUNDED])->sum('amount_paid');
        $totalRefunded = (float) $payments->sum('refunded_amount');
        $netRevenue = max(0, round($grossCollected - $totalRefunded, 2));
        $outstandingBalance = (float) $payments->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])->sum('remaining_balance');

        // Single query for today / month / year revenue instead of 6 queries
        $todayStr = today()->toDateString();
        $monthStartStr = now()->copy()->startOfMonth()->toDateTimeString();
        $yearStartStr = now()->copy()->startOfYear()->toDateTimeString();

        $revAgg = Payment::selectRaw("
            COALESCE(SUM(CASE WHEN payment_date >= ? THEN amount_paid - refunded_amount ELSE 0 END), 0) as today_rev,
            COALESCE(SUM(CASE WHEN payment_date >= ? THEN amount_paid - refunded_amount ELSE 0 END), 0) as month_rev,
            COALESCE(SUM(amount_paid - refunded_amount), 0) as year_rev
        ", [
            $todayStr . ' 00:00:00',
            $monthStartStr,
        ])->where('payment_date', '>=', $yearStartStr)->first();

        $todayRevenue = max(0, round((float) ($revAgg->today_rev ?? 0), 2));
        $monthRevenue = max(0, round((float) ($revAgg->month_rev ?? 0), 2));
        $yearRevenue = max(0, round((float) ($revAgg->year_rev ?? 0), 2));

        // Single query for active & expired membership counts
        $subAgg = Subscription::selectRaw("
            SUM(CASE WHEN status = 'active' AND end_date >= ? THEN 1 ELSE 0 END) as active_cnt,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_cnt
        ", [$todayStr])->first();

        $activeMembershipsCount = (int) ($subAgg->active_cnt ?? 0);
        $expiredMembershipsCount = (int) ($subAgg->expired_cnt ?? 0);

        $revenueByDay = $this->groupRevenueByDay($payments, $fromDate, $toDate);
        $paymentMethodsBreakdown = $payments
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_PAID])
            ->groupBy('payment_method')
            ->map(fn (Collection $items) => round((float) $items->sum('amount_paid') - (float) $items->sum('refunded_amount'), 2));

        $planSales = $subscriptions
            ->groupBy(fn (Subscription $s) => $s->effectivePlanName())
            ->map(fn (Collection $items) => [
                'count' => $items->count(),
                'revenue' => round((float) $items->sum(fn (Subscription $s) => $s->effectivePrice()), 2),
            ]);

        $topActiveMembers = Attendance::selectRaw('member_id, COUNT(*) as visits')
            ->whereBetween('check_in', [$fromDate, $toDate])
            ->groupBy('member_id')
            ->orderByDesc('visits')
            ->with('member:id,name,membership_id')
            ->take(10)
            ->get();

        return compact(
            'fromDate',
            'toDate',
            'payments',
            'subscriptions',
            'newMembers',
            'attendances',
            'grossCollected',
            'totalRefunded',
            'netRevenue',
            'outstandingBalance',
            'todayRevenue',
            'monthRevenue',
            'yearRevenue',
            'activeMembershipsCount',
            'expiredMembershipsCount',
            'revenueByDay',
            'paymentMethodsBreakdown',
            'planSales',
            'topActiveMembers'
        );
    }

    private function groupRevenueByDay(Collection $payments, Carbon $from, Carbon $to): array
    {
        $daysDiff = $from->diffInDays($to);
        $labels = [];
        $values = [];
        $useDaily = $daysDiff <= 31;
        $format = $useDaily ? 'Y-m-d' : 'Y-m';

        $grouped = $payments->groupBy(fn (Payment $p) => $p->payment_date->format($format));
        $cursor = $useDaily ? $from->copy()->startOfDay() : $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $key = $cursor->format($format);
            $labels[] = $cursor->format($useDaily ? 'M d' : 'M Y');
            $bucket = $grouped->get($key);
            $values[] = $bucket
                ? max(0, round((float) $bucket->sum('amount_paid') - (float) $bucket->sum('refunded_amount'), 2))
                : 0.0;
            $useDaily ? $cursor->addDay() : $cursor->addMonth();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
