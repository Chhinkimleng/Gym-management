@extends('layouts.app')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('payments.index') }}" class="hover:text-[#202520]">Payments</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Record</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Record New Payment</h2>
            <p class="text-sm text-[#858B84]">Supports full payments, partial payments, and pending invoices.</p>
        </div>
        <a href="{{ route('payments.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> Back
        </a>
    </div>

    <form method="POST" action="{{ route('payments.store') }}" class="gym-card p-6 sm:p-8 space-y-5">
        @csrf

        <div>
            <label for="member_id" class="gym-label">Member *</label>
            <select name="member_id" id="member_id" required class="gym-input">
                @foreach(isset($member) ? [$member] : $members as $m)
                    <option value="{{ $m->id }}" {{ old('member_id', $selectedMemberId ?? '') == $m->id ? 'selected' : '' }}>
                        {{ $m->name }} ({{ $m->membership_id }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="subscription_id" class="gym-label">Related Membership</label>
            <select name="subscription_id" id="subscription_id" class="gym-input">
                <option value="">-- General / No Specific Membership --</option>
                @foreach($subscriptions as $sub)
                    @php
                        $due = $sub->remainingBalance() > 0 ? $sub->remainingBalance() : $sub->effectivePrice();
                    @endphp
                    <option value="{{ $sub->id }}"
                            data-due="{{ number_format($due, 2, '.', '') }}"
                            {{ old('subscription_id') == $sub->id ? 'selected' : '' }}>
                        {{ $sub->effectivePlanName() }} ({{ $sub->start_date->format('M d, Y') }} – {{ $sub->end_date->format('M d, Y') }})
                        — Due: {{ $curr }}{{ number_format($due, 2) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="total_amount" class="gym-label">Total Charge ({{ $curr }}) *</label>
                <input type="number" step="0.01" min="0.01" name="total_amount" id="total_amount"
                       value="{{ old('total_amount', $subscriptions->first() ? number_format($subscriptions->first()->remainingBalance() > 0 ? $subscriptions->first()->remainingBalance() : $subscriptions->first()->effectivePrice(), 2, '.', '') : '') }}"
                       required class="gym-input tabular-nums">
            </div>

            <div>
                <label for="amount_paid" class="gym-label">Amount Paid Now ({{ $curr }}) *</label>
                <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid"
                       value="{{ old('amount_paid', $subscriptions->first() ? number_format($subscriptions->first()->remainingBalance() > 0 ? $subscriptions->first()->remainingBalance() : $subscriptions->first()->effectivePrice(), 2, '.', '') : '') }}"
                       required class="gym-input tabular-nums">
            </div>

            <div>
                <label class="gym-label">Remaining Balance ({{ $curr }})</label>
                <input type="text" id="balance_preview" readonly class="gym-input font-semibold tabular-nums" value="0.00">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="payment_date" class="gym-label">Payment Date *</label>
                <input id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required class="gym-input">
            </div>

            <div>
                <label for="payment_method" class="gym-label">Payment Method *</label>
                <select id="payment_method" name="payment_method" required class="gym-input">
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="transaction_id" class="gym-label">Transaction / Ref #</label>
                <input id="transaction_id" type="text" name="transaction_id" value="{{ old('transaction_id') }}" class="gym-input" placeholder="Optional bank/POS ref">
            </div>
        </div>

        <div>
            <label for="notes" class="gym-label">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="gym-input" placeholder="Optional receipt notes...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E8EAE3]">
            <a href="{{ route('payments.index') }}" class="btn-outline">Cancel</a>
            <button type="submit" class="btn-lime">
                <x-icon name="check" class="w-4 h-4" />
                <span class="btn-label">Save Payment</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const memberSelect = document.getElementById('member_id');
const subSelect = document.getElementById('subscription_id');
const totalInput = document.getElementById('total_amount');
const paidInput = document.getElementById('amount_paid');
const balancePreview = document.getElementById('balance_preview');
const curr = @json($curr);

function recalcBalance() {
    const t = parseFloat(totalInput.value || '0');
    const p = parseFloat(paidInput.value || '0');
    const rem = Math.max(0, t - p);
    balancePreview.value = rem.toFixed(2);
}

subSelect.addEventListener('change', () => {
    const opt = subSelect.selectedOptions[0];
    if (opt && opt.dataset.due) {
        totalInput.value = opt.dataset.due;
        paidInput.value = opt.dataset.due;
    }
    recalcBalance();
});

totalInput.addEventListener('input', recalcBalance);
paidInput.addEventListener('input', recalcBalance);

memberSelect.addEventListener('change', async () => {
    const memberId = memberSelect.value;
    if (!memberId) return;
    try {
        const res = await fetch(`/members/${memberId}/subscriptions`);
        const subs = await res.json();
        subSelect.innerHTML = '<option value="">-- General / No Specific Membership --</option>';
        subs.forEach((s, idx) => {
            const due = s.remaining_balance > 0 ? s.remaining_balance : s.effective_price;
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.dataset.due = Number(due).toFixed(2);
            opt.textContent = `${s.plan_name} (${s.start_date} – ${s.end_date}) — Due: ${curr}${Number(due).toFixed(2)}`;
            if (idx === 0) opt.selected = true;
            subSelect.appendChild(opt);
        });
        subSelect.dispatchEvent(new Event('change'));
    } catch (e) {
        console.error(e);
    }
});

recalcBalance();
</script>
@endpush
