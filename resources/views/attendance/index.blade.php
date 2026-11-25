@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance Desk')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Attendance</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Live Attendance Desk</h2>
            <p class="text-sm text-[#858B84]">Check members in and out, verify active membership eligibility, and monitor floor occupancy.</p>
        </div>
        <a href="{{ route('attendance.report') }}" class="btn-outline shrink-0">
            <x-icon name="file-text" class="w-4 h-4" /> Full Attendance History
        </a>
    </div>

    <!-- Today's Summary & Instant Check-In Bar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <div class="lg:col-span-5 bg-[#171A18] text-white rounded-2xl p-6 space-y-4">
            <div>
                <span class="badge-pill badge-lime">Quick Check-In</span>
                <h3 class="font-display font-bold text-lg text-white mt-2">Check In by Member ID or Name</h3>
            </div>
            <form action="{{ route('attendance.checkin') }}" method="POST" class="flex flex-col sm:flex-row gap-2.5">
                @csrf
                <input type="text" name="member_lookup" required placeholder="e.g. GYM-2611-0001 or Maya Lin"
                       class="flex-1 px-4 py-2.5 rounded-xl bg-white/10 border border-white/15 text-sm text-white placeholder-[#858B84] focus:outline-none focus:border-[#C5F467]">
                <button type="submit" class="btn-lime shrink-0">
                    <x-icon name="log-in" class="w-4 h-4" /> Check In
                </button>
            </form>
        </div>

        <div class="lg:col-span-7 grid grid-cols-3 gap-4">
            <div class="gym-card p-5 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase text-[#858B84]">Check-Ins Today</span>
                <span class="font-display font-extrabold text-3xl text-[#202520] mt-2 tabular-nums">{{ $todayStats['total_checkins'] }}</span>
                <span class="text-xs text-[#858B84] mt-2">{{ now()->format('M d, Y') }}</span>
            </div>
            <div class="gym-card p-5 flex flex-col justify-between border-l-4 border-l-[#C5F467]">
                <span class="text-xs font-semibold uppercase text-[#858B84]">Currently In Gym</span>
                <span class="font-display font-extrabold text-3xl text-[#267A4B] mt-2 tabular-nums">{{ $todayStats['currently_in'] }}</span>
                <span class="text-xs text-[#858B84] mt-2">Active sessions</span>
            </div>
            <div class="gym-card p-5 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase text-[#858B84]">Checked Out</span>
                <span class="font-display font-extrabold text-3xl text-[#202520] mt-2 tabular-nums">{{ $todayStats['checked_out'] }}</span>
                <span class="text-xs text-[#858B84] mt-2">Completed today</span>
            </div>
        </div>
    </div>

    <!-- Currently Checked In Board -->
    @if($checkedInNow->count() > 0)
        <div class="gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between bg-[#C5F467]/15">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#4CAF78]"></span>
                    <h3 class="font-display font-bold text-base text-[#202520]">Currently Checked In ({{ $checkedInNow->count() }})</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                @foreach($checkedInNow as $att)
                    <div class="p-4 rounded-xl border border-[#E8EAE3] bg-white flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('members.show', $att->member) }}" class="font-semibold text-sm text-[#202520] hover:underline truncate block">
                                {{ $att->member?->name }}
                            </a>
                            <div class="text-xs text-[#858B84]">
                                {{ $att->member?->membership_id }} &bull; In at {{ $att->check_in->format('h:i A') }}
                            </div>
                        </div>
                        <form action="{{ route('attendance.checkout', $att) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-coral py-1.5 px-3 text-xs shrink-0">
                                <x-icon name="log-out" class="w-3.5 h-3.5" /> Out
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Member Check-In Directory -->
    <div class="gym-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#E8EAE3] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-display font-bold text-base text-[#202520]">Member Check-In Directory</h3>
            <form method="GET" action="{{ route('attendance.index') }}" class="flex gap-2" data-no-loading>
                <div class="relative">
                    <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3 top-2.5 pointer-events-none" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Filter by ID, name, phone..." class="gym-input pl-9 py-1.5 text-sm w-60">
                </div>
                <button type="submit" class="btn-dark py-1.5 px-3 text-xs">Find</button>
                @if($search)
                    <a href="{{ route('attendance.index') }}" class="btn-outline py-1.5 px-3 text-xs">Reset</a>
                @endif
            </form>
        </div>

        @if($members->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84] border-b border-[#E8EAE3]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Member</th>
                            <th class="text-left px-4 py-3 font-semibold">Member ID</th>
                            <th class="text-left px-4 py-3 font-semibold">Eligibility</th>
                            <th class="text-left px-4 py-3 font-semibold">Session</th>
                            <th class="text-right px-6 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($members as $member)
                            @php
                                $activeSession = $member->activeAttendance;
                                $eligible = $member->isEligibleForCheckIn();
                            @endphp
                            <tr class="hover:bg-[#F5F5EF]/50">
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('members.show', $member) }}" class="font-semibold text-[#202520] hover:underline">
                                        {{ $member->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-xs text-[#565C55]">{{ $member->membership_id }}</td>
                                <td class="px-4 py-3.5">
                                    @if($eligible)
                                        <span class="badge-pill badge-success">Eligible ({{ $member->activeSubscription?->effectivePlanName() }})</span>
                                    @else
                                        <span class="badge-pill badge-danger">Ineligible</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($activeSession)
                                        <span class="badge-pill badge-lime">In since {{ $activeSession->check_in->format('h:i A') }}</span>
                                    @else
                                        <span class="text-xs text-[#858B84]">Not in gym</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    @if($activeSession)
                                        <form action="{{ route('attendance.checkout', $activeSession) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-coral py-1.5 px-3 text-xs">Check Out</button>
                                        </form>
                                    @elseif($eligible)
                                        <form action="{{ route('attendance.checkin') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="member_id" value="{{ $member->id }}">
                                            <button type="submit" class="btn-lime py-1.5 px-3 text-xs">Check In</button>
                                        </form>
                                    @else
                                        <a href="{{ route('subscriptions.create', $member) }}" class="btn-outline py-1.5 px-3 text-xs">Assign Plan</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $members->links() }}
            </div>
        @else
            <div class="px-6 py-10 text-center text-sm text-[#858B84]">No members match your search.</div>
        @endif
    </div>
</div>
@endsection
