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

        $paymentsQuery = Payment::with(['member', 'subscription.membershipPlan', 'recorder'])
            ->whereBetween('payment_date', [$fromDate, $toDate]);

        $subscriptionsQuery = Subscription::with(['member', 'membershipPlan'])
            ->whereBetween('start_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $membersQuery = Member::with('activeSubscription.membershipPlan')
            ->whereBetween('joining_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $attendanceQuery = Attendance::with(['member', 'recorder'])
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

        $todayRevenue = (float) Payment::whereDate('payment_date', today())->sum('amount_paid')
            - (float) Payment::whereDate('payment_date', today())->sum('refunded_amount');

        $monthRevenue = (float) Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount_paid')
            - (float) Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('refunded_amount');

        $yearRevenue = (float) Payment::whereYear('payment_date', now()->year)->sum('amount_paid')
            - (float) Payment::whereYear('payment_date', now()->year)->sum('refunded_amount');

        $activeMembershipsCount = Subscription::where('status', Subscription::STATUS_ACTIVE)->whereDate('end_date', '>=', now()->toDateString())->count();
        $expiredMembershipsCount = Subscription::where('status', Subscription::STATUS_EXPIRED)->count();

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
            ->with('member')
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

        if ($daysDiff <= 31) {
            $cursor = $from->copy()->startOfDay();
            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('M d');
                $dayPayments = $payments->filter(fn (Payment $p) => $p->payment_date->format('Y-m-d') === $key);
                $values[] = max(0, round((float) $dayPayments->sum('amount_paid') - (float) $dayPayments->sum('refunded_amount'), 2));
                $cursor->addDay();
            }
        } else {
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $monthPayments = $payments->filter(fn (Payment $p) => $p->payment_date->format('Y-m') === $key);
                $values[] = max(0, round((float) $monthPayments->sum('amount_paid') - (float) $monthPayments->sum('refunded_amount'), 2));
                $cursor->addMonth();
            }
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
