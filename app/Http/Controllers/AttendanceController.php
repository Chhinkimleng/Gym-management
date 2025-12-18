<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
{
    $members = Member::with(['attendances'])->latest()->paginate(15);
    return view('attendance.index', compact('members'));
}


    public function checkIn(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $member_id = $request->member_id;

        // Check if member already checked in today and not checked out
        $existing = Attendance::where('member_id', $member_id)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->first();

        if ($existing) {
            return back()->with('error', 'Member already checked in and not checked out.');
        }

        Attendance::create([
            'member_id' => $member_id,
            'check_in' => Carbon::now(),
        ]);

        return back()->with('success', 'Check-in successful.');
    }

    public function checkOut(Attendance $attendance)
    {
        if ($attendance->check_out) {
            return back()->with('error', 'Member already checked out.');
        }

        $attendance->update([
            'check_out' => Carbon::now(),
        ]);

        return back()->with('success', 'Check-out successful.');
    }
    public function memberAttendance(Member $member)
{
    $attendances = $member->attendances()->latest()->paginate(15);

    return view('attendance.member', compact('member', 'attendances'));
}
public function attendances()
{
    return $this->hasMany(Attendance::class);
}
public function report()
{
    $attendances = \App\Models\Attendance::with('member')
                        ->latest()
                        ->paginate(20);

    return view('attendance.report', compact('attendances'));
}


}
