@extends('layouts.app')

@section('title', $payment->payment_reference ?? 'Payment #' . $payment->id)
@section('page-title', 'Payment Details')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('payments.index') }}" class="hover:text-[#202520]">Payments</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ $payment->payment_reference ?? '#' . $payment->id }}</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('payments.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> All Payments
        </a>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('payments.receipt', $payment) }}" class="btn-lime">
                <x-icon name="download" class="w-4 h-4" /> Download PDF Receipt
            </a>
            <a href="{{ route('payments.edit', $payment) }}" class="btn-outline">
                <x-icon name="edit" class="w-4 h-4" /> Edit Details
            </a>
            @if(Auth::user()->isAdmin())
                <form action="{{ route('payments.destroy', $payment) }}" method="POST"
                      data-confirm="Archive payment {{ $payment->payment_reference }}?"
                      data-confirm-title="Archive Payment">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger-soft">
                        <x-icon name="trash-2" class="w-4 h-4" /> Archive
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Payment Summary -->
        <div class="lg:col-span-7 space-y-6">
            <div class="gym-card p-6 sm:p-8 space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="font-mono text-xs font-bold text-[#858B84]">{{ $payment->payment_reference ?? '#' . $payment->id }}</span>
                        <h2 class="font-display font-bold text-2xl text-[#202520] mt-1">
                            {{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}
                        </h2>
                        <p class="text-xs text-[#858B84]">Recorded on {{ $payment->payment_date->format('M d, Y h:i A') }}</p>
                    </div>
                    @php
                        $pBadge = match($payment->status) {
                            'paid' => 'badge-success',
                            'partially_paid', 'pending' => 'badge-warning',
                            'refunded' => 'badge-coral',
                            default => 'badge-danger',
                        };
                    @endphp
                    <span class="badge-pill {{ $pBadge }}">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</span>
                </div>

                <div class="grid grid-cols-3 gap-3 pt-4 border-t border-[#E8EAE3]">
                    <div class="p-3.5 rounded-xl bg-[#F5F5EF]">
                        <div class="text-[11px] font-semibold uppercase text-[#858B84]">Total Due</div>
                        <div class="font-display font-bold text-base text-[#202520] tabular-nums">{{ $curr }}{{ number_format((float) $payment->total_amount, 2) }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-[#4CAF78]/10">
                        <div class="text-[11px] font-semibold uppercase text-[#267A4B]">Amount Paid</div>
                        <div class="font-display font-bold text-base text-[#267A4B] tabular-nums">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl {{ (float) $payment->remaining_balance > 0 ? 'bg-[#E76B62]/10' : 'bg-[#F5F5EF]' }}">
                        <div class="text-[11px] font-semibold uppercase text-[#858B84]">Remaining</div>
                        <div class="font-display font-bold text-base {{ (float) $payment->remaining_balance > 0 ? 'text-[#B83A31]' : 'text-[#202520]' }} tabular-nums">
                            {{ $curr }}{{ number_format((float) $payment->remaining_balance, 2) }}
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#E8EAE3] space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Member</span>
                        <a href="{{ route('members.show', $payment->member) }}" class="font-semibold text-[#202520] hover:underline">
                            {{ $payment->member?->name }} ({{ $payment->member?->membership_id }})
                        </a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Membership Plan</span>
                        <span class="font-semibold text-[#202520]">
                            @if($payment->subscription)
                                <a href="{{ route('subscriptions.show', $payment->subscription) }}" class="hover:underline">
                                    {{ $payment->subscription->effectivePlanName() }}
                                </a>
                            @else
                                General Charge
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Payment Method</span>
                        <span class="font-semibold text-[#202520]">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Transaction ID</span>
                        <span class="font-mono text-xs text-[#202520]">{{ $payment->transaction_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#858B84]">Recorded By</span>
                        <span class="text-[#202520]">{{ $payment->recorder?->name ?? 'System' }}</span>
                    </div>
                    @if((float) $payment->refunded_amount > 0)
                        <div class="flex justify-between text-[#C9472B]">
                            <span>Refunded</span>
                            <span class="font-semibold tabular-nums">{{ $curr }}{{ number_format((float) $payment->refunded_amount, 2) }} ({{ $payment->refund_reason }})</span>
                        </div>
                    @endif
                </div>

                @if($payment->notes)
                    <div class="p-3.5 rounded-xl bg-[#F5F5EF] text-xs text-[#565C55] whitespace-pre-line">
                        <span class="font-semibold text-[#202520] block mb-0.5">Notes</span>
                        {{ $payment->notes }}
                    </div>
                @endif
            </div>

            <!-- Audit Trail -->
            <div class="gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3]">
                    <h3 class="font-display font-bold text-base text-[#202520]">Financial Audit Trail</h3>
                </div>
                @if($auditLogs->count() > 0)
                    <div class="divide-y divide-[#E8EAE3]">
                        @foreach($auditLogs as $log)
                            <div class="px-6 py-3.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-[#202520]">{{ $log->description }}</span>
                                    <span class="text-[#858B84]">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="text-[#858B84] mt-0.5">By: {{ $log->user?->name ?? 'System' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-6 text-xs text-[#858B84]">No audit events recorded for this transaction yet.</div>
                @endif
            </div>
        </div>

        <!-- Right Column: Follow-up Balance Payment & Admin Refund -->
        <div class="lg:col-span-5 space-y-6">
            @if((float) $payment->remaining_balance > 0)
                <div class="gym-card p-6 border-l-4 border-l-[#C5F467]">
                    <h3 class="font-display font-bold text-base text-[#202520]">Pay Remaining Balance</h3>
                    <p class="text-xs text-[#858B84] mt-1 mb-4">
                        Creates a new payment receipt without overwriting this historical record.
                    </p>
                    <form action="{{ route('payments.installment', $payment) }}" method="POST" class="space-y-3.5">
                        @csrf
                        <div>
                            <label class="gym-label">Amount ({{ $curr }}) *</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $payment->remaining_balance }}"
                                   name="amount_paid" value="{{ number_format((float) $payment->remaining_balance, 2, '.', '') }}"
                                   required class="gym-input tabular-nums">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="gym-label">Date *</label>
                                <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="gym-input">
                            </div>
                            <div>
                                <label class="gym-label">Method *</label>
                                <select name="payment_method" required class="gym-input">
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="gym-label">Notes</label>
                            <input type="text" name="notes" class="gym-input" placeholder="Balance settlement">
                        </div>
                        <button type="submit" class="btn-lime w-full">
                            <x-icon name="check" class="w-4 h-4" /> Record Balance Payment
                        </button>
                    </form>
                </div>
            @endif

            @if(Auth::user()->isAdmin() && $payment->isRefundable())
                <div class="gym-card p-6 border-l-4 border-l-[#FF795B]">
                    <h3 class="font-display font-bold text-base text-[#202520]">Process Refund (Admin)</h3>
                    <p class="text-xs text-[#858B84] mt-1 mb-4">
                        Max refundable: <strong>{{ $curr }}{{ number_format($payment->netAmount(), 2) }}</strong>
                    </p>
                    <form action="{{ route('payments.refund', $payment) }}" method="POST" class="space-y-3.5"
                          data-confirm="Process this refund? This action is recorded permanently in the audit log."
                          data-confirm-title="Confirm Refund">
                        @csrf
                        <div>
                            <label class="gym-label">Refund Amount ({{ $curr }}) *</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $payment->netAmount() }}"
                                   name="refund_amount" value="{{ number_format($payment->netAmount(), 2, '.', '') }}"
                                   required class="gym-input tabular-nums">
                        </div>
                        <div>
                            <label class="gym-label">Refund Reason *</label>
                            <input type="text" name="refund_reason" required class="gym-input" placeholder="e.g. Duplicate charge / plan cancelled">
                        </div>
                        <button type="submit" class="btn-coral w-full">
                            <x-icon name="rotate-ccw" class="w-4 h-4" /> Issue Refund
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
