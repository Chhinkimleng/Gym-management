@extends('layouts.app')

@section('title', 'Memberships')
@section('page-title', 'Memberships')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Memberships</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Memberships &amp; Renewals</h2>
            <p class="text-sm text-[#858B84]">Track active contracts, upcoming expirations, and membership history.</p>
        </div>
        <a href="{{ route('subscriptions.create') }}" class="btn-lime shrink-0">
            <x-icon name="plus" class="w-4 h-4" /> Assign Membership
        </a>
    </div>

    <!-- Status Tabs & Search -->
    <div class="gym-card p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            @php $current = request('status', ''); @endphp
            @foreach([
                '' => 'All (' . $counts['all'] . ')',
                'active' => 'Active (' . $counts['active'] . ')',
                'expiring' => 'Expiring in ' . $noticeDays . 'd (' . $counts['expiring'] . ')',
                'expired' => 'Expired (' . $counts['expired'] . ')',
                'pending' => 'Pending (' . $counts['pending'] . ')',
                'cancelled' => 'Cancelled (' . $counts['cancelled'] . ')',
            ] as $key => $label)
                <a href="{{ route('memberships.index', array_filter(['status' => $key, 'search' => request('search')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $current === $key ? 'bg-[#171A18] text-[#C5F467]' : 'bg-[#F5F5EF] text-[#565C55] hover:bg-[#E8EAE3]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('memberships.index') }}" class="flex flex-col sm:flex-row gap-3" data-no-loading>
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative flex-1">
                <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3.5 top-3 pointer-events-none" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by member name, ID, or plan..." class="gym-input pl-10">
            </div>
            <button type="submit" class="btn-dark">Search</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('memberships.index') }}" class="btn-outline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Memberships Table -->
    <div class="gym-card overflow-hidden">
        @if($subscriptions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3.5 font-semibold">Member</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Plan &amp; Locked Price</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Period</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Balance</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Status</th>
                            <th class="text-right px-6 py-3.5 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($subscriptions as $sub)
                            @php
                                $st = $sub->computedStatus();
                                $bal = $sub->remainingBalance();
                            @endphp
                            <tr class="hover:bg-[#F5F5EF]/50 transition">
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('members.show', $sub->member) }}" class="font-semibold text-[#202520] hover:underline">
                                        {{ $sub->member?->name }}
                                    </a>
                                    <div class="font-mono text-xs text-[#858B84]">{{ $sub->member?->membership_id }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-[#202520]">{{ $sub->effectivePlanName() }}</div>
                                    <div class="text-xs text-[#858B84] tabular-nums">
                                        {{ $curr }}{{ number_format($sub->effectivePrice(), 2) }} &bull; {{ $sub->effectiveDurationLabel() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#565C55]">
                                    <div>{{ $sub->start_date->format('M d, Y') }} &rarr; {{ $sub->end_date->format('M d, Y') }}</div>
                                    @if($st === 'active')
                                        <div class="text-[#267A4B] font-semibold mt-0.5">{{ $sub->daysRemaining() }} days left</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-xs font-semibold">
                                    @if($bal > 0)
                                        <span class="text-[#B83A31]">{{ $curr }}{{ number_format($bal, 2) }} due</span>
                                    @else
                                        <span class="text-[#267A4B]">Settled</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="badge-pill {{ $st === 'active' ? 'badge-success' : ($st === 'pending' ? 'badge-warning' : ($st === 'expired' ? 'badge-neutral' : 'badge-danger')) }}">
                                        {{ ucfirst($st) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('subscriptions.show', $sub) }}" class="btn-outline py-1.5 px-3 text-xs">Details</a>
                                        @if($st !== 'cancelled')
                                            <form action="{{ route('subscriptions.renew', $sub) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#C5F467] hover:bg-[#B7EA52] text-[#171A18] text-xs font-semibold cursor-pointer">
                                                    Renew
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $subscriptions->links() }}
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <x-icon name="id-card" class="w-10 h-10 text-[#858B84] mx-auto mb-3" />
                <h3 class="font-display font-bold text-lg text-[#202520]">No memberships match this filter</h3>
                <p class="text-sm text-[#858B84] mt-1 mb-5">Assign a membership plan to a member to get started.</p>
                <a href="{{ route('subscriptions.create') }}" class="btn-lime">Assign Membership</a>
            </div>
        @endif
    </div>
</div>
@endsection
