@extends('layouts.app')

@section('title', 'Membership #' . $subscription->id)
@section('page-title', 'Membership Details')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('memberships.index') }}" class="hover:text-[#202520]">Memberships</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">#{{ $subscription->id }}</span>
@endsection

@section('content')
@php
    $curr = $currencySymbol ?? '$';
    $st = $subscription->computedStatus();
    $balance = $subscription->remainingBalance();
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('members.show', $subscription->member) }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Member Profile
        </a>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($balance > 0 && $st !== 'cancelled')
                <a href="{{ route('members.payments.create', $subscription->member) }}" class="btn-lime">
                    <x-icon name="wallet" class="w-4 h-4" /> Record Payment ({{ $curr }}{{ number_format($balance, 2) }} due)
                </a>
            @endif

            @if($st !== 'cancelled')
                <form action="{{ route('subscriptions.renew', $subscription) }}" method="POST"
                      data-confirm="Create a renewal membership starting after {{ $subscription->end_date->format('M d, Y') }}? Historical records stay intact."
                      data-confirm-title="Renew Membership">
                    @csrf
                    <button type="submit" class="btn-dark">
                        <x-icon name="refresh-cw" class="w-4 h-4" /> Renew Membership
                    </button>
                </form>
            @endif

            @if($st === 'active' || $st === 'pending')
                <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST"
                      data-confirm="Cancel this membership? The member will no longer be eligible to check in."
                      data-confirm-title="Cancel Membership">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-danger-soft">
                        <x-icon name="ban" class="w-4 h-4" /> Cancel Membership
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5 gym-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <span class="badge-pill {{ $st === 'active' ? 'badge-success' : ($st === 'pending' ? 'badge-warning' : ($st === 'expired' ? 'badge-neutral' : 'badge-danger')) }}">
                    {{ ucfirst($st) }}
                </span>
                <span class="text-xs text-[#858B84]">Contract #{{ $subscription->id }}</span>
            </div>

            <div>
                <h2 class="font-display font-bold text-2xl text-[#202520]">{{ $subscription->effectivePlanName() }}</h2>
                <p class="text-sm text-[#858B84]">
                    Member: <a href="{{ route('members.show', $subscription->member) }}" class="font-semibold text-[#202520] hover:underline">{{ $subscription->member?->name }}</a>
                    ({{ $subscription->member?->membership_id }})
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="p-3.5 rounded-xl bg-[#F5F5EF]">
                    <div class="text-[11px] uppercase font-semibold text-[#858B84]">Contract Price</div>
                    <div class="font-display font-bold text-lg text-[#202520] tabular-nums">{{ $curr }}{{ number_format($subscription->effectivePrice(), 2) }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-[#F5F5EF]">
                    <div class="text-[11px] uppercase font-semibold text-[#858B84]">Remaining Due</div>
                    <div class="font-display font-bold text-lg {{ $balance > 0 ? 'text-[#B83A31]' : 'text-[#267A4B]' }} tabular-nums">
                        {{ $curr }}{{ number_format($balance, 2) }}
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-[#E8EAE3] space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-[#858B84]">Duration</span>
                    <span class="font-semibold text-[#202520]">{{ $subscription->effectiveDurationLabel() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#858B84]">Start Date</span>
                    <span class="font-semibold text-[#202520]">{{ $subscription->start_date->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#858B84]">End Date</span>
                    <span class="font-semibold text-[#202520]">{{ $subscription->end_date->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#858B84]">Days Remaining</span>
                    <span class="font-semibold text-[#202520]">{{ $st === 'active' ? $subscription->daysRemaining() . ' days' : 'N/A' }}</span>
                </div>
                @if($subscription->cancelled_at)
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Cancelled At</span>
                        <span class="font-semibold text-[#B83A31]">{{ $subscription->cancelled_at->format('M d, Y h:i A') }}</span>
                    </div>
                @endif
            </div>

            @if($subscription->notes)
                <div class="p-3 rounded-xl bg-[#F5F5EF] text-xs text-[#565C55]">
                    <span class="font-semibold text-[#202520] block mb-0.5">Notes</span>
                    {{ $subscription->notes }}
                </div>
            @endif
        </div>

        <div class="lg:col-span-7 gym-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-[#202520]">Linked Payments</h3>
                <a href="{{ route('members.payments.create', $subscription->member) }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">+ Record Payment</a>
            </div>
            @if($subscription->payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84]">
                            <tr>
                                <th class="text-left px-6 py-2.5 font-semibold">Reference</th>
                                <th class="text-left px-4 py-2.5 font-semibold">Paid</th>
                                <th class="text-left px-4 py-2.5 font-semibold">Method</th>
                                <th class="text-left px-4 py-2.5 font-semibold">Status</th>
                                <th class="text-right px-6 py-2.5 font-semibold">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAE3]">
                            @foreach($subscription->payments as $payment)
                                <tr>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs font-semibold text-[#202520] hover:underline">
                                            {{ $payment->payment_reference ?? '#' . $payment->id }}
                                        </a>
                                        <div class="text-xs text-[#858B84]">{{ $payment->payment_date->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold tabular-nums">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</td>
                                    <td class="px-4 py-3 text-xs">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge-pill {{ $payment->status === 'paid' ? 'badge-success' : ($payment->status === 'refunded' ? 'badge-coral' : 'badge-warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('payments.receipt', $payment) }}" class="text-xs font-semibold text-[#202520] hover:underline">PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-10 text-center text-sm text-[#858B84]">No payments recorded for this membership yet.</div>
            @endif
        </div>
    </div>
</div>
@endsection
