<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        Subscription::syncExpiredStatuses();

        $noticeDays = max(1, (int) Setting::getValue('expiring_notice_days', 7));

        $data = Cache::remember('dashboard_metrics_v1', 30, function () use ($noticeDays) {
            $today = now()->toDateString();
            $yesterday = now()->copy()->subDay()->toDateString();
            $monthStart = now()->copy()->startOfMonth();
            $sixMonthsAgo = now()->copy()->startOfMonth()->subMonths(5);
            $sevenDaysAgo = now()->copy()->subDays(6)->startOfDay();
            $lastMonth = now()->copy()->subMonthNoOverflow();

            // 1. Member aggregates + 6-month trend in 2 lean queries
            $memberStats = Member::selectRaw("
                COUNT(*) as total_members,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN joining_date >= ? AND joining_date <= ? THEN 1 ELSE 0 END) as new_this_month,
                SUM(CASE WHEN joining_date >= ? AND joining_date <= ? THEN 1 ELSE 0 END) as new_last_month,
                SUM(CASE WHEN joining_date < ? THEN 1 ELSE 0 END) as baseline_before_window
            ", [
                $monthStart->toDateString(),
                $monthStart->copy()->endOfMonth()->toDateString(),
                $lastMonth->copy()->startOfMonth()->toDateString(),
                $lastMonth->copy()->endOfMonth()->toDateString(),
                $sixMonthsAgo->toDateString(),
            ])->first();

            $totalMembers = (int) ($memberStats->total_members ?? 0);
            $activeMembers = (int) ($memberStats->active_members ?? 0);
            $inactiveMembers = $totalMembers - $activeMembers;
            $newMembersThisMonth = (int) ($memberStats->new_this_month ?? 0);
            $newMembersLastMonth = (int) ($memberStats->new_last_month ?? 0);
            $runningTotal = (int) ($memberStats->baseline_before_window ?? 0);

            $recentJoinDates = Member::where('joining_date', '>=', $sixMonthsAgo->toDateString())
                ->pluck('joining_date')
                ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
                ->countBy();

            // 2. Subscription aggregates in 1 query
            $subStats = Subscription::selectRaw("
                SUM(CASE WHEN status = 'active' AND end_date >= ? THEN 1 ELSE 0 END) as active_subs,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_subs,
                SUM(CASE WHEN status = 'active' AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon
            ", [$today, $today, now()->copy()->addDays($noticeDays)->toDateString()])->first();

            $activeSubscriptions = (int) ($subStats->active_subs ?? 0);
            $expiredMemberships = (int) ($subStats->expired_subs ?? 0);
            $expiringSoon = (int) ($subStats->expiring_soon ?? 0);

            // 3. Payment aggregates + 6-month revenue in 2 lean queries
            $recentWindowPayments = Payment::select(['amount_paid', 'refunded_amount', 'payment_date', 'status'])
                ->where('payment_date', '>=', $sixMonthsAgo->copy()->startOfDay())
                ->get();

            $pendingStats = Payment::selectRaw("
                COUNT(*) as pending_count,
                COALESCE(SUM(remaining_balance), 0) as pending_amount
            ")->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
                ->where('remaining_balance', '>', 0)
                ->first();

            $pendingPaymentsCount = (int) ($pendingStats->pending_count ?? 0);
            $pendingPaymentsAmount = round((float) ($pendingStats->pending_amount ?? 0), 2);

            $currentYm = now()->format('Y-m');
            $lastYm = $lastMonth->format('Y-m');

            $netForFilter = fn ($collection) => max(0, round(
                (float) $collection->sum('amount_paid') - (float) $collection->sum('refunded_amount'),
                2
            ));

            $monthlyRevenue = $netForFilter($recentWindowPayments->filter(fn ($p) => $p->payment_date->format('Y-m') === $currentYm));
            $lastMonthRevenue = $netForFilter($recentWindowPayments->filter(fn ($p) => $p->payment_date->format('Y-m') === $lastYm));
            $todayRevenue = $netForFilter($recentWindowPayments->filter(fn ($p) => $p->payment_date->toDateString() === $today));

            $revenueGrowthPct = $lastMonthRevenue > 0
                ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                : null;

            $monthlyLabels = [];
            $monthlyRevenueData = [];
            $newMembersTrend = [];
            $memberGrowthData = [];

            for ($i = 5; $i >= 0; $i--) {
                $monthDate = now()->copy()->startOfMonth()->subMonths($i);
                $ym = $monthDate->format('Y-m');
                $monthlyLabels[] = $monthDate->format('M Y');

                $monthlyRevenueData[] = $netForFilter($recentWindowPayments->filter(fn ($p) => $p->payment_date->format('Y-m') === $ym));
                $joinedCount = (int) ($recentJoinDates[$ym] ?? 0);
                $newMembersTrend[] = $joinedCount;
                $runningTotal += $joinedCount;
                $memberGrowthData[] = $runningTotal;
            }

            // 4. Attendance aggregates + 7-day trend in 1 query
            $recentAttendances = Attendance::select(['check_in', 'check_out'])
                ->where('check_in', '>=', $sevenDaysAgo)
                ->get();

            $todayAttendance = $recentAttendances->filter(fn ($a) => $a->check_in->toDateString() === $today)->count();
            $yesterdayAttendance = $recentAttendances->filter(fn ($a) => $a->check_in->toDateString() === $yesterday)->count();
            $currentlyCheckedIn = Attendance::whereNull('check_out')->count();

            $attendanceLabels = [];
            $attendanceData = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->copy()->subDays($i);
                $dStr = $day->toDateString();
                $attendanceLabels[] = $day->format('D');
                $attendanceData[] = $recentAttendances->filter(fn ($a) => $a->check_in->toDateString() === $dStr)->count();
            }

            // 5. Recent activity lists & plan distribution
            $recentMembers = Member::with('activeSubscription:id,member_id,membership_plan_id,plan_name,end_date')
                ->select(['id', 'membership_id', 'name', 'joining_date', 'status'])
                ->latest()
                ->take(5)
                ->get();

            $recentPayments = Payment::with('member:id,name')
                ->select(['id', 'payment_reference', 'member_id', 'amount_paid', 'payment_method', 'status', 'payment_date'])
                ->latest('payment_date')
                ->take(5)
                ->get();

            $expiringMembers = Subscription::with('member:id,name')
                ->select(['id', 'member_id', 'membership_plan_id', 'plan_name', 'end_date', 'status'])
                ->where('status', Subscription::STATUS_ACTIVE)
                ->whereBetween('end_date', [$today, now()->copy()->addDays($noticeDays)->toDateString()])
                ->orderBy('end_date')
                ->take(6)
                ->get();

            $todayAttendanceList = Attendance::with('member:id,name')
                ->select(['id', 'member_id', 'check_in', 'check_out'])
                ->latest('check_in')
                ->take(6)
                ->get();

            $planDistribution = MembershipPlan::withCount('activeSubscriptions')
                ->select(['id', 'name', 'is_active'])
                ->orderByDesc('active_subscriptions_count')
                ->get()
                ->filter(fn ($plan) => $plan->active_subscriptions_count > 0 || $plan->is_active)
                ->values();

            $distributionLabels = $planDistribution->pluck('name')->toArray();
            $distributionCounts = $planDistribution->pluck('active_subscriptions_count')->toArray();

            return compact(
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
            );
        });

        return view('dashboard', $data);
    }
}
