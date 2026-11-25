<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{
    public function checkIn(Member $member, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($member, $notes) {
            $lockedMember = Member::lockForUpdate()->findOrFail($member->id);

            if ($lockedMember->status !== Member::STATUS_ACTIVE) {
                throw new InvalidArgumentException("Member {$lockedMember->name} is {$lockedMember->status} and cannot check in.");
            }

            Subscription::syncExpiredStatuses();

            if (!$lockedMember->isEligibleForCheckIn()) {
                throw new InvalidArgumentException("Member {$lockedMember->name} does not have an active, valid membership.");
            }

            $existing = Attendance::where('member_id', $lockedMember->id)
                ->whereNull('check_out')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException("{$lockedMember->name} is already checked in (since {$existing->check_in->format('h:i A')}).");
            }

            $attendance = Attendance::create([
                'member_id' => $lockedMember->id,
                'check_in' => now(),
                'status' => Attendance::STATUS_CHECKED_IN,
                'recorded_by' => auth()->id(),
                'notes' => $notes,
            ]);

            ActivityLog::record(
                'attendance.checkin',
                "Checked in {$lockedMember->name} ({$lockedMember->membership_id}).",
                $attendance
            );

            return $attendance;
        });
    }

    public function checkOut(Attendance $attendance): Attendance
    {
        return DB::transaction(function () use ($attendance) {
            $locked = Attendance::lockForUpdate()->findOrFail($attendance->id);

            if ($locked->check_out !== null) {
                throw new InvalidArgumentException('Member has already checked out for this session.');
            }

            $locked->update([
                'check_out' => now(),
                'status' => Attendance::STATUS_CHECKED_OUT,
            ]);

            ActivityLog::record(
                'attendance.checkout',
                "Checked out {$locked->member?->name} (Duration: {$locked->durationFormatted()}).",
                $locked
            );

            return $locked;
        });
    }
}
