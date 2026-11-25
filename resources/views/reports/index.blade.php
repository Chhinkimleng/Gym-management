@extends('layouts.app')

@section('title', __('Reports'))
@section('page-title', __('Reports'))
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">{{ __('Dashboard') }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ __('Reports') }}</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-6">
    <!-- Header & Exports -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">{{ __('Business Reports & Exports') }}</h2>
            <p class="text-sm text-[#858B84]">Real-time revenue, membership sales, attendance, and member activity.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('reports.export.csv', request()->query()) }}" class="btn-outline">
                <x-icon name="download" class="w-4 h-4" /> {{ __('Export CSV') }}
            </a>
            <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn-lime">
                <x-icon name="file-text" class="w-4 h-4" /> {{ __('Export PDF') }}
            </a>
            <button type="button" onclick="window.print()" class="btn-dark">
                <x-icon name="printer" class="w-4 h-4" /> {{ __('Print') }}
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="gym-card p-4 no-print">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3" data-no-loading>
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="lg:col-span-3">
                <label class="gym-label text-xs">From Date</label>
                <input type="date" name="from" value="{{ $from }}" class="gym-input">
            </div>
            <div class="lg:col-span-3">
                <label class="gym-label text-xs">To Date</label>
                <input type="date" name="to" value="{{ $to }}" class="gym-input">
            </div>
            <div class="lg:col-span-4">
                <label class="gym-label text-xs">Search Member / Reference</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Optional name, ID, or ref..." class="gym-input">
            </div>
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="submit" class="btn-dark w-full">Run Report</button>
            </div>
        </form>
    </div>

    <!-- Period Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#171A18] text-white rounded-2xl p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Period Net Revenue</div>
            <div class="font-display font-extrabold text-2xl text-[#C5F467] mt-1.5 tabular-nums">{{ $curr }}{{ number_format($netRevenue, 2) }}</div>
            <div class="text-xs text-[#A9AFA7] mt-1">Today: {{ $curr }}{{ number_format($todayRevenue, 2) }}</div>
        </div>
        <div class="gym-card p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">This Month / Year</div>
            <div class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ $curr }}{{ number_format($monthRevenue, 2) }}</div>
            <div class="text-xs text-[#858B84] mt-1">YTD: {{ $curr }}{{ number_format($yearRevenue, 2) }}</div>
        </div>
        <div class="gym-card p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Memberships (Active / Exp)</div>
            <div class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ $activeMembershipsCount }} / {{ $expiredMembershipsCount }}</div>
            <div class="text-xs text-[#858B84] mt-1">{{ $subscriptions->count() }} sold in range</div>
        </div>
        <div class="gym-card p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Attendance &amp; Sign-Ups</div>
            <div class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ $attendances->count() }} visits</div>
            <div class="text-xs text-[#858B84] mt-1">{{ $newMembers->count() }} new members</div>
        </div>
    </div>

    <!-- Revenue & Method Breakdown Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <div class="lg:col-span-8 gym-card p-6">
            <h3 class="font-display font-bold text-base text-[#202520] mb-4">Revenue Over Selected Period</h3>
            <div class="h-60">
                <canvas id="reportRevenueChart"></canvas>
            </div>
        </div>
        <div class="lg:col-span-4 gym-card p-6 space-y-4">
            <h3 class="font-display font-bold text-base text-[#202520]">Plan Sales Breakdown</h3>
            @forelse($planSales as $planName => $stats)
                <div class="flex items-center justify-between text-sm py-2 border-b border-[#E8EAE3] last:border-0">
                    <div>
                        <div class="font-semibold text-[#202520]">{{ $planName }}</div>
                        <div class="text-xs text-[#858B84]">{{ $stats['count'] }} {{ $stats['count'] === 1 ? 'sale' : 'sales' }}</div>
                    </div>
                    <span class="font-display font-bold text-[#202520] tabular-nums">{{ $curr }}{{ number_format($stats['revenue'], 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-[#858B84]">No membership sales in this date range.</p>
            @endforelse
        </div>
    </div>

    <!-- Report Tabs -->
    <div class="flex flex-wrap items-center gap-2 no-print">
        @foreach([
            'revenue' => 'Payments (' . $payments->count() . ')',
            'memberships' => 'Membership Sales (' . $subscriptions->count() . ')',
            'members' => 'New Registrations (' . $newMembers->count() . ')',
            'attendance' => 'Attendance (' . $attendances->count() . ')',
        ] as $tKey => $tLabel)
            <a href="{{ route('reports.index', array_merge(request()->query(), ['tab' => $tKey])) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $tab === $tKey ? 'bg-[#171A18] text-[#C5F467]' : 'bg-white border border-[#E8EAE3] text-[#565C55] hover:bg-[#F5F5EF]' }}">
                {{ $tLabel }}
            </a>
        @endforeach
    </div>

    <!-- Tab Table -->
    <div class="gym-card overflow-hidden">
        @if($tab === 'memberships')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Member</th>
                            <th class="text-left px-4 py-3 font-semibold">Plan</th>
                            <th class="text-left px-4 py-3 font-semibold">Price</th>
                            <th class="text-left px-4 py-3 font-semibold">Period</th>
                            <th class="text-right px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @forelse($subscriptions as $sub)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-[#202520]">{{ $sub->member?->name }}</td>
                                <td class="px-4 py-3">{{ $sub->effectivePlanName() }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $curr }}{{ number_format($sub->effectivePrice(), 2) }}</td>
                                <td class="px-4 py-3 text-xs text-[#565C55]">{{ $sub->start_date->format('M d, Y') }} – {{ $sub->end_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-right"><span class="badge-pill badge-neutral">{{ ucfirst($sub->computedStatus()) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-[#858B84]">No membership sales in range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'members')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Member ID</th>
                            <th class="text-left px-4 py-3 font-semibold">Name</th>
                            <th class="text-left px-4 py-3 font-semibold">Email</th>
                            <th class="text-left px-4 py-3 font-semibold">Joined</th>
                            <th class="text-right px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @forelse($newMembers as $m)
                            <tr>
                                <td class="px-6 py-3 font-mono text-xs">{{ $m->membership_id }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $m->name }}</td>
                                <td class="px-4 py-3 text-xs text-[#565C55]">{{ $m->email }}</td>
                                <td class="px-4 py-3 text-xs">{{ $m->joining_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-right"><span class="badge-pill badge-success">{{ ucfirst($m->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-[#858B84]">No new registrations in range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'attendance')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Member</th>
                            <th class="text-left px-4 py-3 font-semibold">Check In</th>
                            <th class="text-left px-4 py-3 font-semibold">Check Out</th>
                            <th class="text-right px-6 py-3 font-semibold">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @forelse($attendances as $att)
                            <tr>
                                <td class="px-6 py-3 font-semibold">{{ $att->member?->name }}</td>
                                <td class="px-4 py-3 text-xs">{{ $att->check_in->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3 text-xs">{{ $att->check_out?->format('M d, Y h:i A') ?? 'Active' }}</td>
                                <td class="px-6 py-3 text-right text-xs font-semibold">{{ $att->durationFormatted() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-[#858B84]">No attendance logs in range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold">Reference</th>
                            <th class="text-left px-4 py-3 font-semibold">Member</th>
                            <th class="text-left px-4 py-3 font-semibold">Paid</th>
                            <th class="text-left px-4 py-3 font-semibold">Remaining</th>
                            <th class="text-left px-4 py-3 font-semibold">Method</th>
                            <th class="text-right px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @forelse($payments as $p)
                            <tr>
                                <td class="px-6 py-3 font-mono text-xs font-semibold">{{ $p->payment_reference ?? '#' . $p->id }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $p->member?->name }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $curr }}{{ number_format((float) $p->amount_paid, 2) }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $curr }}{{ number_format((float) $p->remaining_balance, 2) }}</td>
                                <td class="px-4 py-3 text-xs">{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                                <td class="px-6 py-3 text-right"><span class="badge-pill badge-neutral">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-[#858B84]">No payments in range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const curr = @json($curr);
    new Chart(document.getElementById('reportRevenueChart'), {
        type: 'bar',
        data: {
            labels: @json($revenueByDay['labels']),
            datasets: [{
                label: 'Net Revenue',
                data: @json($revenueByDay['values']),
                backgroundColor: '#C5F467',
                borderColor: '#171A18',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: v => curr + v.toLocaleString() }, grid: { color: '#E8EAE3' } }
            }
        }
    });
});
</script>
@endpush
