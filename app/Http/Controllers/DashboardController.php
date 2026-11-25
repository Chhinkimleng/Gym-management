<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        Subscription::syncExpiredStatuses();

        $noticeDays = max(1, (int) Setting::getValue('expiring_notice_days', 7));

        // 7 Core KPIs from real database records
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', Member::STATUS_ACTIVE)->count();
        $inactiveMembers = Member::where('status', '!=', Member::STATUS_ACTIVE)->count();

        $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->count();

        $expiredMemberships = Subscription::where('status', Subscription::STATUS_EXPIRED)->count();

        $newMembersThisMonth = Member::whereMonth('joining_date', now()->month)
            ->whereYear('joining_date', now()->year)
            ->count();

        $lastMonth = now()->copy()->subMonthNoOverflow();
        $newMembersLastMonth = Member::whereMonth('joining_date', $lastMonth->month)
            ->whereYear('joining_date', $lastMonth->year)
            ->count();

        $monthlyRevenue = max(0, round(
            (float) Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_PAID, Payment::STATUS_REFUNDED])
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount_paid')
            - (float) Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('refunded_amount'),
            2
        ));

        $lastMonthRevenue = max(0, round(
            (float) Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_PAID, Payment::STATUS_REFUNDED])
                ->whereMonth('payment_date', $lastMonth->month)
                ->whereYear('payment_date', $lastMonth->year)
                ->sum('amount_paid')
            - (float) Payment::whereMonth('payment_date', $lastMonth->month)
                ->whereYear('payment_date', $lastMonth->year)
                ->sum('refunded_amount'),
            2
        ));

        $revenueGrowthPct = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        $todayRevenue = max(0, round(
            (float) Payment::whereDate('payment_date', today())->sum('amount_paid')
            - (float) Payment::whereDate('payment_date', today())->sum('refunded_amount'),
            2
        ));

        $pendingPaymentsCount = Payment::whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
            ->where('remaining_balance', '>', 0)
            ->count();

        $pendingPaymentsAmount = round((float) Payment::whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
            ->sum('remaining_balance'), 2);

        $todayAttendance = Attendance::whereDate('check_in', today())->count();
        $yesterdayAttendance = Attendance::whereDate('check_in', today()->subDay())->count();
        $currentlyCheckedIn = Attendance::whereNull('check_out')->count();

        $expiringSoon = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($noticeDays)->toDateString()])
            ->count();

        // Recent activity lists
        $recentMembers = Member::with('activeSubscription.membershipPlan')
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::with(['member', 'subscription.membershipPlan'])
            ->latest('payment_date')
            ->take(5)
            ->get();

        $expiringMembers = Subscription::with(['member', 'membershipPlan'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($noticeDays)->toDateString()])
            ->orderBy('end_date')
            ->take(6)
            ->get();

        $todayAttendanceList = Attendance::with('member')
            ->latest('check_in')
            ->take(6)
            ->get();

        // Monthly revenue & member registration trends (last 6 months)
        $monthlyLabels = [];
        $monthlyRevenueData = [];
        $newMembersTrend = [];
        $memberGrowthData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->copy()->startOfMonth()->subMonths($i);
            $monthEnd = $monthDate->copy()->endOfMonth();
            $monthlyLabels[] = $monthDate->format('M Y');

            $rev = (float) Payment::whereBetween('payment_date', [$monthDate->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                ->sum('amount_paid')
                - (float) Payment::whereBetween('payment_date', [$monthDate->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                ->sum('refunded_amount');
            $monthlyRevenueData[] = max(0, round($rev, 2));

            $newMembersTrend[] = Member::whereBetween('joining_date', [$monthDate->toDateString(), $monthEnd->toDateString()])->count();
            $memberGrowthData[] = Member::whereDate('joining_date', '<=', $monthEnd->toDateString())->count();
        }

        // Weekly attendance chart (last 7 days)
        $attendanceLabels = [];
        $attendanceData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->copy()->subDays($i);
            $attendanceLabels[] = $day->format('D');
            $attendanceData[] = Attendance::whereDate('check_in', $day->toDateString())->count();
        }

        // Membership distribution across active plans
        $planDistribution = MembershipPlan::withCount(['activeSubscriptions'])
            ->orderByDesc('active_subscriptions_count')
            ->get()
            ->filter(fn ($plan) => $plan->active_subscriptions_count > 0 || $plan->is_active)
            ->values();

        $distributionLabels = $planDistribution->pluck('name')->toArray();
        $distributionCounts = $planDistribution->pluck('active_subscriptions_count')->toArray();

        return view('dashboard', compact(
            'totalMembers',
            'activeMembers',
            'inactiveMembers',
            'activeSubscriptions',
            'expiredMemberships',
            'newMembersThisMonth',
            'newMembersLastMonth',
            'expiringSoon',
            'noticeDays',
            'todayAttendance',
            'yesterdayAttendance',
            'currentlyCheckedIn',
            'monthlyRevenue',
            'lastMonthRevenue',
            'revenueGrowthPct',
            'todayRevenue',
            'pendingPaymentsCount',
            'pendingPaymentsAmount',
            'recentMembers',
            'recentPayments',
            'todayAttendanceList',
            'expiringMembers',
            'monthlyLabels',
            'monthlyRevenueData',
            'newMembersTrend',
            'memberGrowthData',
            'attendanceLabels',
            'attendanceData',
            'distributionLabels',
            'distributionCounts'
        ));
    }
}
