@extends('layouts.app')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))
@section('breadcrumbs')
    <span>{{ $gymName ?? 'PulseCore Fitness' }}</span>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ __('Overview') }}</span>
@endsection

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
    $firstName = explode(' ', Auth::user()->name)[0];
    $curr = $currencySymbol ?? '$';
@endphp

<div class="space-y-6">
    <!-- Welcome Banner -->
    <section class="bg-[#171A18] text-white rounded-2xl p-6 sm:p-7 border border-white/10 shadow-sm relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-[#C5F467] text-xs font-semibold mb-3">
                    <x-icon name="calendar" class="w-3.5 h-3.5" />
                    <span>{{ now()->format('l, F j, Y') }}</span>
                </div>
                <h2 class="font-display font-extrabold text-2xl sm:text-3xl tracking-tight text-white">
                    {{ $greeting }}, {{ $firstName }}
                </h2>
                <p class="text-sm text-[#B7BDB5] mt-1.5 max-w-2xl">
                    {{ __('Every check-in builds momentum. Keep member retention high, follow up on expiring plans, and keep the floor energized today.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('members.create') }}" class="btn-lime">
                    <x-icon name="user-plus" class="w-4 h-4" />
                    <span>{{ __('Add Member') }}</span>
                </a>
                <a href="{{ route('payments.create') }}" class="btn-coral">
                    <x-icon name="wallet" class="w-4 h-4" />
                    <span>{{ __('Record Payment') }}</span>
                </a>
                <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/16 text-white text-sm font-semibold border border-white/15 transition">
                    <x-icon name="calendar-check" class="w-4 h-4 text-[#C5F467]" />
                    <span>{{ __('Check-In Desk') }}</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 7 Real-Data Statistics Cards (Asymmetric Bento Grid) -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- 1. Monthly Revenue (Featured Dark/Lime Card) -->
        <div class="sm:col-span-2 xl:col-span-1 bg-[#171A18] text-white rounded-2xl p-5 border border-white/10 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('Monthly Revenue') }}</p>
                    <p class="font-display font-extrabold text-3xl text-white mt-2 tabular-nums">
                        {{ $curr }}{{ number_format($monthlyRevenue, 2) }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#C5F467] text-[#171A18] flex items-center justify-center shrink-0">
                    <x-icon name="wallet" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                <span class="text-[#A9AFA7]">{{ __('Today:') }} <strong class="text-white tabular-nums">{{ $curr }}{{ number_format($todayRevenue, 2) }}</strong></span>
                @if($revenueGrowthPct !== null)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold {{ $revenueGrowthPct >= 0 ? 'bg-[#C5F467]/20 text-[#C5F467]' : 'bg-[#FF795B]/20 text-[#FF795B]' }}">
                        {{ $revenueGrowthPct >= 0 ? '+' : '' }}{{ $revenueGrowthPct }}%
                    </span>
                @else
                    <span class="text-[#858B84]">{{ __('Current month') }}</span>
                @endif
            </div>
        </div>

        <!-- 2. Total Members -->
        <div class="gym-card gym-card-hover p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('Total Members') }}</p>
                    <p class="font-display font-extrabold text-3xl text-[#202520] mt-2 tabular-nums">{{ number_format($totalMembers) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#C5F467]/35 text-[#202520] flex items-center justify-center shrink-0">
                    <x-icon name="users" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#E8EAE3] flex items-center justify-between text-xs text-[#858B84]">
                <span>{{ __('Active rate:') }} <strong class="text-[#202520]">{{ $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100) : 0 }}%</strong></span>
                <span>{{ __('Inactive:') }} <strong class="text-[#202520]">{{ $inactiveMembers }}</strong></span>
            </div>
        </div>

        <!-- 3. Active Members -->
        <div class="gym-card gym-card-hover p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('Active Members') }}</p>
                    <p class="font-display font-extrabold text-3xl text-[#202520] mt-2 tabular-nums">{{ number_format($activeMembers) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#4CAF78]/15 text-[#267A4B] flex items-center justify-center shrink-0">
                    <x-icon name="user-check" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#E8EAE3] flex items-center justify-between text-xs text-[#858B84]">
                <span>{{ __('Active plans:') }} <strong class="text-[#267A4B]">{{ $activeSubscriptions }}</strong></span>
                <span>({{ $noticeDays }}d): <strong class="text-[#9A6708]">{{ $expiringSoon }}</strong></span>
            </div>
        </div>

        <!-- 4. Today's Attendance -->
        <div class="gym-card gym-card-hover p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __("Today's Attendance") }}</p>
                    <p class="font-display font-extrabold text-3xl text-[#202520] mt-2 tabular-nums">{{ number_format($todayAttendance) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#FF795B]/15 text-[#C9472B] flex items-center justify-center shrink-0">
                    <x-icon name="calendar-check" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#E8EAE3] flex items-center justify-between text-xs text-[#858B84]">
                <span>{{ __('In gym now:') }} <strong class="text-[#267A4B]">{{ $currentlyCheckedIn }}</strong></span>
                <span>{{ __('Yesterday:') }} <strong class="text-[#202520]">{{ $yesterdayAttendance }}</strong></span>
            </div>
        </div>

        <!-- 5. New Members This Month -->
        <div class="gym-card gym-card-hover p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('New Members (Month)') }}</p>
                    <p class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ number_format($newMembersThisMonth) }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-[#F5F5EF] border border-[#E8EAE3] text-[#202520] flex items-center justify-center">
                    <x-icon name="trending-up" class="w-4 h-4" />
                </div>
            </div>
            <p class="text-xs text-[#858B84] mt-3">
                <strong class="text-[#202520]">{{ $newMembersLastMonth }}</strong>
            </p>
        </div>

        <!-- 6. Expired Memberships -->
        <div class="gym-card gym-card-hover p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('Expired Memberships') }}</p>
                    <p class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ number_format($expiredMemberships) }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-[#F2B84B]/18 text-[#9A6708] flex items-center justify-center">
                    <x-icon name="clock" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="text-[#858B84]">{{ __('Renewal candidates') }}</span>
                <a href="{{ route('memberships.index', ['status' => 'expired']) }}" class="font-semibold text-[#202520] hover:underline">{{ __('Review') }} &rarr;</a>
            </div>
        </div>

        <!-- 7. Pending Payments -->
        <div class="sm:col-span-2 gym-card gym-card-hover p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-[#E76B62]/14 text-[#B83A31] flex items-center justify-center shrink-0">
                    <x-icon name="alert-circle" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#858B84]">{{ __('Pending Payments & Balances') }}</p>
                    <div class="flex items-baseline gap-3 mt-1">
                        <span class="font-display font-extrabold text-2xl text-[#202520] tabular-nums">{{ $curr }}{{ number_format($pendingPaymentsAmount, 2) }}</span>
                        <span class="badge-pill {{ $pendingPaymentsCount > 0 ? 'badge-warning' : 'badge-success' }}">
                            {{ $pendingPaymentsCount }}
                        </span>
                    </div>
                </div>
            </div>
            <a href="{{ route('payments.index', ['status' => 'partially_paid']) }}" class="btn-outline shrink-0">
                {{ __('View Open Balances') }}
            </a>
        </div>
    </section>

    <!-- Analytics Charts Grid -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Monthly Revenue Chart -->
        <div class="lg:col-span-8 gym-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Monthly Revenue Trend') }}</h3>
                    <p class="text-xs text-[#858B84]">{{ __('Net collected payments over the last 6 months') }}</p>
                </div>
                <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B] inline-flex items-center gap-1">
                    {{ __('Full Reports') }} <x-icon name="arrow-up-right" class="w-3.5 h-3.5" />
                </a>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Membership Distribution -->
        <div class="lg:col-span-4 gym-card p-6">
            <div class="mb-4">
                <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Membership Distribution') }}</h3>
                <p class="text-xs text-[#858B84]">{{ __('Active subscribers across plans') }}</p>
            </div>
            @if(array_sum($distributionCounts) > 0)
                <div class="h-64 flex items-center justify-center">
                    <canvas id="planDistributionChart"></canvas>
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-center text-xs text-[#858B84]">
                    <x-icon name="layers" class="w-8 h-8 mb-2 text-[#C6CBC4]" />
                    <p>{{ __('No active plan subscriptions yet.') }}</p>
                </div>
            @endif
        </div>

        <!-- Weekly Attendance Chart -->
        <div class="lg:col-span-6 gym-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Weekly Attendance') }}</h3>
                    <p class="text-xs text-[#858B84]">{{ __('Daily member check-ins (last 7 days)') }}</p>
                </div>
                <a href="{{ route('attendance.report') }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">{{ __('Full Attendance History') }} &rarr;</a>
            </div>
            <div class="h-56">
                <canvas id="weeklyAttendanceChart"></canvas>
            </div>
        </div>

        <!-- Member Registration Trends -->
        <div class="lg:col-span-6 gym-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Member Registration Trends') }}</h3>
                    <p class="text-xs text-[#858B84]">{{ __('Monthly new sign-ups & cumulative base') }}</p>
                </div>
                <a href="{{ route('members.index') }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">{{ __('All Members') }} &rarr;</a>
            </div>
            <div class="h-56">
                <canvas id="memberTrendChart"></canvas>
            </div>
        </div>
    </section>

    <!-- Recent Activity Grid -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Upcoming Expirations -->
        <div class="gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#F2B84B]"></span>
                    <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Upcoming Membership Expirations') }}</h3>
                </div>
                <a href="{{ route('memberships.index', ['status' => 'expiring']) }}" class="text-xs font-semibold text-[#858B84] hover:text-[#202520]">{{ __('View all') }}</a>
            </div>
            @if($expiringMembers->count() > 0)
                <div class="divide-y divide-[#E8EAE3]">
                    @foreach($expiringMembers as $sub)
                        <div class="px-6 py-3.5 flex items-center justify-between gap-3 hover:bg-[#F5F5EF]/60 transition">
                            <div class="min-w-0">
                                <a href="{{ route('members.show', $sub->member) }}" class="text-sm font-semibold text-[#202520] hover:underline truncate block">
                                    {{ $sub->member?->name }}
                                </a>
                                <p class="text-xs text-[#858B84] truncate">
                                    {{ $sub->effectivePlanName() }} &bull; {{ $sub->end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="badge-pill badge-warning">{{ $sub->daysRemaining() }}d</span>
                                <form action="{{ route('subscriptions.renew', $sub) }}" method="POST" data-no-loading>
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#C5F467] hover:bg-[#B7EA52] text-[#171A18] text-xs font-semibold cursor-pointer">
                                        {{ __('Renew') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center text-sm text-[#858B84]">
                    {{ __('No active plan subscriptions yet.') }}
                </div>
            @endif
        </div>

        <!-- Recent Attendance -->
        <div class="gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#4CAF78]"></span>
                    <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Recent Attendance Records') }}</h3>
                </div>
                <a href="{{ route('attendance.index') }}" class="text-xs font-semibold text-[#858B84] hover:text-[#202520]">{{ __('Check-In Desk') }}</a>
            </div>
            @if($todayAttendanceList->count() > 0)
                <div class="divide-y divide-[#E8EAE3]">
                    @foreach($todayAttendanceList as $att)
                        <div class="px-6 py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[#F5F5EF] border border-[#E8EAE3] text-xs font-bold text-[#202520] flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($att->member?->name ?? 'M', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('members.show', $att->member) }}" class="text-sm font-semibold text-[#202520] hover:underline truncate block">
                                        {{ $att->member?->name }}
                                    </a>
                                    <p class="text-xs text-[#858B84]">{{ $att->check_in->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                            <span class="badge-pill {{ $att->check_out ? 'badge-neutral' : 'badge-lime' }}">
                                {{ $att->check_out ? $att->durationFormatted() : __('In Gym') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center text-sm text-[#858B84]">
                    {{ __('No attendance check-ins recorded yet.') }}
                </div>
            @endif
        </div>

        <!-- Recently Registered Members -->
        <div class="gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Recently Registered Members') }}</h3>
                <a href="{{ route('members.index') }}" class="text-xs font-semibold text-[#858B84] hover:text-[#202520]">{{ __('View all') }}</a>
            </div>
            @if($recentMembers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F5EF]/70 text-xs uppercase text-[#858B84]">
                            <tr>
                                <th class="text-left px-6 py-2.5 font-semibold">{{ __('Member') }}</th>
                                <th class="text-left px-4 py-2.5 font-semibold">{{ __('Current Plan') }}</th>
                                <th class="text-left px-4 py-2.5 font-semibold">{{ __('Joined') }}</th>
                                <th class="text-right px-6 py-2.5 font-semibold">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAE3]">
                            @foreach($recentMembers as $member)
                                <tr class="hover:bg-[#F5F5EF]/50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('members.show', $member) }}" class="font-semibold text-[#202520] hover:underline">{{ $member->name }}</a>
                                        <div class="text-xs text-[#858B84]">{{ $member->membership_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#565C55]">
                                        {{ $member->activeSubscription?->effectivePlanName() ?? __('No Active Plan') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#858B84]">{{ $member->joining_date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <span class="badge-pill {{ $member->status === 'active' ? 'badge-success' : ($member->status === 'inactive' ? 'badge-neutral' : 'badge-danger') }}">
                                            {{ __(ucfirst($member->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-10 text-center text-sm text-[#858B84]">{{ __('No members registered yet.') }}</div>
            @endif
        </div>

        <!-- Recent Payments -->
        <div class="gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-[#202520]">{{ __('Recent Payments') }}</h3>
                <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-[#858B84] hover:text-[#202520]">{{ __('View all') }}</a>
            </div>
            @if($recentPayments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F5EF]/70 text-xs uppercase text-[#858B84]">
                            <tr>
                                <th class="text-left px-6 py-2.5 font-semibold">{{ __('Member') }}</th>
                                <th class="text-left px-4 py-2.5 font-semibold">{{ __('Paid') }}</th>
                                <th class="text-left px-4 py-2.5 font-semibold">Method</th>
                                <th class="text-right px-6 py-2.5 font-semibold">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAE3]">
                            @foreach($recentPayments as $payment)
                                <tr class="hover:bg-[#F5F5EF]/50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('payments.show', $payment) }}" class="font-semibold text-[#202520] hover:underline">
                                            {{ $payment->member?->name }}
                                        </a>
                                        <div class="text-xs text-[#858B84]">{{ $payment->payment_reference ?? '#' . $payment->id }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-[#202520] tabular-nums">
                                        {{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#565C55]">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        @php
                                            $pBadge = match($payment->status) {
                                                'paid' => 'badge-success',
                                                'partially_paid', 'pending' => 'badge-warning',
                                                'refunded' => 'badge-coral',
                                                default => 'badge-danger',
                                            };
                                        @endphp
                                        <span class="badge-pill {{ $pBadge }}">{{ __(ucwords(str_replace('_', ' ', $payment->status))) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-10 text-center text-sm text-[#858B84]">{{ __('No payments recorded yet.') }}</div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const currency = @json($curr);
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#858B84';

    // 1. Monthly Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($monthlyLabels),
            datasets: [{
                label: 'Net Revenue',
                data: @json($monthlyRevenueData),
                borderColor: '#171A18',
                backgroundColor: 'rgba(197, 244, 103, 0.35)',
                borderWidth: 2.5,
                pointBackgroundColor: '#C5F467',
                pointBorderColor: '#171A18',
                pointBorderWidth: 2,
                pointRadius: 4,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#E8EAE3' },
                    ticks: { callback: v => currency + v.toLocaleString() }
                }
            }
        }
    });

    // 2. Plan Distribution Doughnut
    const distCanvas = document.getElementById('planDistributionChart');
    if (distCanvas) {
        new Chart(distCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($distributionLabels),
                datasets: [{
                    data: @json($distributionCounts),
                    backgroundColor: ['#C5F467', '#171A18', '#FF795B', '#4CAF78', '#F2B84B'],
                    borderColor: '#FFFFFF',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true, padding: 14 }
                    }
                }
            }
        });
    }

    // 3. Weekly Attendance Bar Chart
    new Chart(document.getElementById('weeklyAttendanceChart'), {
        type: 'bar',
        data: {
            labels: @json($attendanceLabels),
            datasets: [{
                label: 'Check-ins',
                data: @json($attendanceData),
                backgroundColor: (ctx) => ctx.dataIndex === 6 ? '#FF795B' : '#C5F467',
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E8EAE3' } }
            }
        }
    });

    // 4. Member Registration Trends
    new Chart(document.getElementById('memberTrendChart'), {
        type: 'bar',
        data: {
            labels: @json($monthlyLabels),
            datasets: [
                {
                    label: 'New Sign-ups',
                    data: @json($newMembersTrend),
                    backgroundColor: '#C5F467',
                    borderRadius: 8
                },
                {
                    label: 'Total Members',
                    data: @json($memberGrowthData),
                    type: 'line',
                    borderColor: '#171A18',
                    backgroundColor: '#171A18',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E8EAE3' } }
            }
        }
    });
});
</script>
@endpush
