<?php



namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();
        $inactiveMembers = Member::where('status', 'inactive')->count();
        
        // Active subscriptions (not expired)
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->count();
        
        // Expiring soon (within 7 days)
        $expiringSoon = Subscription::where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->count();
        
        // Today's attendance
        $todayAttendance = Attendance::whereDate('check_in', today())->count();
        
        // This month's revenue
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        
        // Today's revenue
        $todayRevenue = Payment::where('status', 'paid')
            ->whereDate('payment_date', today())
            ->sum('amount');
        
        // Recent members (last 5)
        $recentMembers = Member::with('activeSubscription.membershipPlan')
            ->latest()
            ->take(5)
            ->get();
        
        // Recent payments (last 5)
        $recentPayments = Payment::with(['member', 'subscription.membershipPlan'])
            ->where('status', 'paid')
            ->latest()
            ->take(5)
            ->get();
        // $recentPayments = \App\Models\Payment::with('member')
        //             ->latest()
        //             ->take(5)
        //             ->get();

        // Today's attendance list
        $todayAttendanceList = Attendance::with('member')
            ->whereDate('check_in', today())
            ->latest()
            ->take(10)
            ->get();
        
        // Members with expiring subscriptions (next 7 days)
        $expiringMembers = Subscription::with(['member', 'membershipPlan'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->orderBy('end_date', 'asc')
            ->get();
        
        // Monthly revenue chart data (last 6 months)
        $monthlyRevenueData = [];
        $monthlyLabels = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyLabels[] = $date->format('M Y');
            $monthlyRevenueData[] = Payment::where('status', 'paid')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');
        }
        
        // Member growth chart data (last 6 months)
        $memberGrowthData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $memberGrowthData[] = Member::whereYear('joining_date', '<=', $date->year)
                ->whereMonth('joining_date', '<=', $date->month)
                ->count();
        }
        
        // Attendance trend (last 7 days)
        $attendanceData = [];
        $attendanceLabels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $attendanceLabels[] = $date->format('D, M j');
            $attendanceData[] = Attendance::whereDate('check_in', $date)->count();
        }

        return view('dashboard', compact(
            'totalMembers',
            'activeMembers',
            'inactiveMembers',
            'activeSubscriptions',
            'expiringSoon',
            'todayAttendance',
            'monthlyRevenue',
            'todayRevenue',
            'recentMembers',
            'recentPayments',
            'todayAttendanceList',
            'expiringMembers',
            'monthlyRevenueData',
            'monthlyLabels',
            'memberGrowthData',
            'attendanceData',
            'attendanceLabels'
        ));
    }
}
