@extends('layouts.app')

@section('title', $member->name . ' Attendance')
@section('page-title', 'Member Attendance')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('members.show', $member) }}" class="hover:text-[#202520]">{{ $member->name }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Attendance</span>
@endsection

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">{{ $member->name }} — Attendance Log</h2>
            <p class="text-sm text-[#858B84]">Member ID: {{ $member->membership_id }} &bull; Total Visits: {{ $attendances->total() }}</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('members.show', $member) }}" class="btn-outline">
                <x-icon name="arrow-left" class="w-4 h-4" /> Back to Profile
            </a>
            @if($member->isEligibleForCheckIn() && !$member->activeAttendance)
                <form method="POST" action="{{ route('attendance.checkin') }}">
                    @csrf
                    <input type="hidden" name="member_id" value="{{ $member->id }}">
                    <button type="submit" class="btn-lime">
                        <x-icon name="log-in" class="w-4 h-4" /> Check In
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="gym-card overflow-hidden">
        @if($attendances->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Check-In</th>
                            <th class="text-left px-4 py-3 font-semibold">Check-Out</th>
                            <th class="text-left px-4 py-3 font-semibold">Duration</th>
                            <th class="text-left px-4 py-3 font-semibold">Recorded By</th>
                            <th class="text-right px-6 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($attendances as $att)
                            <tr>
                                <td class="px-6 py-3.5 font-medium text-[#202520]">{{ $att->check_in->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3.5">
                                    @if($att->check_out)
                                        {{ $att->check_out->format('M d, Y h:i A') }}
                                    @else
                                        <span class="badge-pill badge-lime">In Gym</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs font-semibold">{{ $att->durationFormatted() }}</td>
                                <td class="px-4 py-3.5 text-xs text-[#858B84]">{{ $att->recorder?->name ?? 'System' }}</td>
                                <td class="px-6 py-3.5 text-right">
                                    @if(!$att->check_out)
                                        <form method="POST" action="{{ route('attendance.checkout', $att) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-coral py-1.5 px-3 text-xs">Check Out</button>
                                        </form>
                                    @else
                                        <span class="badge-pill badge-neutral">Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $attendances->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-[#858B84]">No attendance records yet for this member.</div>
        @endif
    </div>
</div>
@endsection
