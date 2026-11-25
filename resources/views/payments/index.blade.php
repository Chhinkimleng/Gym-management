@extends('layouts.app')

@section('title', __('Payments'))
@section('page-title', __('Payments'))
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">{{ __('Dashboard') }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ __('Payments') }}</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">{{ __('Payment Ledger') }}</h2>
            <p class="text-sm text-[#858B84]">Record full or partial payments, track balances, process refunds, and download receipts.</p>
        </div>
        <a href="{{ route('payments.create') }}" class="btn-lime shrink-0">
            <x-icon name="plus" class="w-4 h-4" /> {{ __('Record Payment') }}
        </a>
    </div>

    <!-- Summary KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-[#171A18] text-white rounded-2xl p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Net Collected (Filtered)</div>
            <div class="font-display font-extrabold text-2xl text-[#C5F467] mt-1.5 tabular-nums">{{ $curr }}{{ number_format($totalCollected, 2) }}</div>
        </div>
        <div class="gym-card p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Outstanding Balances</div>
            <div class="font-display font-extrabold text-2xl {{ $totalOutstanding > 0 ? 'text-[#B83A31]' : 'text-[#202520]' }} mt-1.5 tabular-nums">
                {{ $curr }}{{ number_format($totalOutstanding, 2) }}
            </div>
        </div>
        <div class="gym-card p-5">
            <div class="text-xs uppercase font-semibold text-[#858B84]">Refunded Amount</div>
            <div class="font-display font-extrabold text-2xl text-[#202520] mt-1.5 tabular-nums">{{ $curr }}{{ number_format($totalRefunded, 2) }}</div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="gym-card p-4">
        <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3" data-no-loading>
            <div class="lg:col-span-4 relative">
                <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3.5 top-3 pointer-events-none" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, member name, or TXN ID..." class="gym-input pl-10">
            </div>
            <div class="lg:col-span-2">
                <select name="status" class="gym-input">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Payment::statuses() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <select name="payment_method" class="gym-input">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <input type="date" name="from" value="{{ request('from') }}" class="gym-input" title="From Date">
            </div>
            <div class="lg:col-span-2 flex gap-2">
                <input type="date" name="to" value="{{ request('to') }}" class="gym-input" title="To Date">
                <button type="submit" class="btn-dark shrink-0">Go</button>
                @if(request()->hasAny(['search', 'status', 'payment_method', 'from', 'to']))
                    <a href="{{ route('payments.index') }}" class="btn-outline shrink-0" title="Reset">
                        <x-icon name="rotate-ccw" class="w-4 h-4" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="gym-card overflow-hidden">
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3.5 font-semibold">Reference</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Member</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Membership</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Amount Paid</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Balance</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Method</th>
                            <th class="text-left px-4 py-3.5 font-semibold">Status</th>
                            <th class="text-right px-6 py-3.5 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-[#F5F5EF]/50 transition">
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs font-bold text-[#202520] hover:underline">
                                        {{ $payment->payment_reference ?? '#' . $payment->id }}
                                    </a>
                                    <div class="text-xs text-[#858B84]">{{ $payment->payment_date->format('M d, Y') }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <a href="{{ route('members.show', $payment->member) }}" class="font-semibold text-[#202520] hover:underline">
                                        {{ $payment->member?->name }}
                                    </a>
                                    <div class="text-xs text-[#858B84]">{{ $payment->member?->membership_id }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#565C55]">
                                    {{ $payment->subscription?->effectivePlanName() ?? 'General Charge' }}
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-[#202520] tabular-nums">
                                    {{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}
                                    @if((float) $payment->refunded_amount > 0)
                                        <div class="text-[11px] text-[#C9472B]">-{{ $curr }}{{ number_format((float) $payment->refunded_amount, 2) }} ref.</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-xs font-semibold">
                                    @if((float) $payment->remaining_balance > 0)
                                        <span class="text-[#B83A31]">{{ $curr }}{{ number_format((float) $payment->remaining_balance, 2) }}</span>
                                    @else
                                        <span class="text-[#858B84]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#565C55]">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $pBadge = match($payment->status) {
                                            'paid' => 'badge-success',
                                            'partially_paid', 'pending' => 'badge-warning',
                                            'refunded' => 'badge-coral',
                                            default => 'badge-danger',
                                        };
                                    @endphp
                                    <span class="badge-pill {{ $pBadge }}">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('payments.show', $payment) }}" class="p-2 rounded-lg border border-[#E8EAE3] hover:bg-[#F5F5EF]" title="Details">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('payments.receipt', $payment) }}" class="p-2 rounded-lg border border-[#E8EAE3] hover:bg-[#F5F5EF]" title="Download PDF Receipt">
                                            <x-icon name="download" class="w-4 h-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $payments->links() }}
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <x-icon name="wallet" class="w-10 h-10 text-[#858B84] mx-auto mb-3" />
                <h3 class="font-display font-bold text-lg text-[#202520]">No payment records found</h3>
                <p class="text-sm text-[#858B84] mt-1 mb-5">Record a payment for a member or adjust your date/status filters.</p>
                <a href="{{ route('payments.create') }}" class="btn-lime">Record Payment</a>
            </div>
        @endif
    </div>
</div>
@endsection
