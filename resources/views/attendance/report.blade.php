@extends('layouts.app')

@section('title', 'Attendance History')
@section('page-title', 'Attendance History')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('attendance.index') }}" class="hover:text-[#202520]">Attendance</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">History</span>
@endsection

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Attendance History</h2>
            <p class="text-sm text-[#858B84]">Filter check-ins and check-outs by date range, status, or member.</p>
        </div>
        <a href="{{ route('attendance.index') }}" class="btn-outline shrink-0">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back to Desk
        </a>
    </div>

    <div class="gym-card p-4">
        <form method="GET" action="{{ route('attendance.report') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3" data-no-loading>
            <div class="lg:col-span-4 relative">
                <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3.5 top-3 pointer-events-none" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Member name or ID..." class="gym-input pl-10">
            </div>
            <div class="lg:col-span-2">
                <input type="date" name="from" value="{{ request('from') }}" class="gym-input" title="From Date">
            </div>
            <div class="lg:col-span-2">
                <input type="date" name="to" value="{{ request('to') }}" class="gym-input" title="To Date">
            </div>
            <div class="lg:col-span-2">
                <select name="status" class="gym-input">
                    <option value="">All Sessions</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>In Gym Now</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Checked Out</option>
                </select>
            </div>
            <div class="lg:col-span-2 flex gap-2">
                <button type="submit" class="btn-dark flex-1">Filter</button>
                @if(request()->hasAny(['search', 'from', 'to', 'date', 'status']))
                    <a href="{{ route('attendance.report') }}" class="btn-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="gym-card overflow-hidden">
        @if($attendances->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3.5 font-semibold">Member</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Member ID</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Check-In</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Check-Out</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Duration</th>
                            <th class="text-right px-6 py-3.5 font-semibold">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($attendances as $att)
                            <tr class="hover:bg-[#F5F5EF]/50">
                                <td class="px-6 py-3.5 font-semibold text-[#202520]">
                                    <a href="{{ route('members.show', $att->member) }}" class="hover:underline">{{ $att->member?->name }}</a>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-xs text-[#565C55]">{{ $att->member?->membership_id }}</td>
                                <td class="px-4 py-3.5 text-xs text-[#202520]">{{ $att->check_in->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3.5 text-xs">
                                    @if($att->check_out)
                                        {{ $att->check_out->format('M d, Y h:i A') }}
                                    @else
                                        <span class="badge-pill badge-lime">In Gym</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs font-semibold">{{ $att->durationFormatted() }}</td>
                                <td class="px-6 py-3.5 text-right text-xs text-[#858B84]">{{ $att->recorder?->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $attendances->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-[#858B84]">No attendance records match your filters.</div>
        @endif
    </div>
</div>
@endsection
