<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Subscription;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function index(Request $request)
    {
        Subscription::syncExpiredStatuses();

        $search = trim((string) $request->input('search'));

        $checkedInNow = Attendance::with('member:id,name,membership_id')
            ->select(['id', 'member_id', 'check_in'])
            ->whereNull('check_out')
            ->latest('check_in')
            ->get();

        $membersQuery = Member::with([
            'activeSubscription:id,member_id,membership_plan_id,plan_name,end_date',
            'activeAttendance:id,member_id,check_in',
        ])
            ->select(['id', 'name', 'membership_id', 'status'])
            ->orderBy('name');

        if ($search !== '') {
            $membersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('membership_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $members = $membersQuery->paginate(12)->withQueryString();

        $todayAgg = Attendance::selectRaw("
            COUNT(*) as total_checkins,
            SUM(CASE WHEN check_out IS NOT NULL THEN 1 ELSE 0 END) as checked_out
        ")->whereBetween('check_in', [today()->startOfDay(), today()->endOfDay()])->first();

        $todayStats = [
            'total_checkins' => (int) ($todayAgg->total_checkins ?? 0),
            'currently_in' => $checkedInNow->count(),
            'checked_out' => (int) ($todayAgg->checked_out ?? 0),
        ];

        return view('attendance.index', compact('members', 'checkedInNow', 'todayStats', 'search'));
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'member_lookup' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $member = null;
        if (!empty($validated['member_id'])) {
            $member = Member::findOrFail($validated['member_id']);
        } elseif (!empty($validated['member_lookup'])) {
            $lookup = trim($validated['member_lookup']);
            $member = Member::where('membership_id', $lookup)
                ->orWhere('name', 'like', "%{$lookup}%")
                ->first();

            if (!$member) {
                return back()->with('error', "No member found matching '{$lookup}'.");
            }
        } else {
            return back()->with('error', 'Please select or enter a member ID or name.');
        }

        try {
            $this->attendanceService->checkIn($member, $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$member->name} checked in successfully.");
    }

    public function checkOut(Attendance $attendance)
    {
        try {
            $updated = $this->attendanceService->checkOut($attendance);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$updated->member?->name} checked out ({$updated->durationFormatted()}).");
    }

    public function memberAttendance(Member $member)
    {
        $attendances = $member->attendances()->with('recorder')->latest('check_in')->paginate(15);

        return view('attendance.member', compact('member', 'attendances'));
    }

    public function report(Request $request)
    {
        $query = Attendance::with(['member', 'recorder']);

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('membership_id', 'like', "%{$search}%");
            });
        }

        if ($date = $request->input('date')) {
            $query->whereDate('check_in', $date);
        } else {
            if ($from = $request->input('from')) {
                $query->whereDate('check_in', '>=', $from);
            }
            if ($to = $request->input('to')) {
                $query->whereDate('check_in', '<=', $to);
            }
        }

        if ($request->input('status') === 'active') {
            $query->whereNull('check_out');
        } elseif ($request->input('status') === 'completed') {
            $query->whereNotNull('check_out');
        }

        $attendances = $query->latest('check_in')->paginate(20)->withQueryString();

        return view('attendance.report', compact('attendances'));
    }
}
